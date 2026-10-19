<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Infra\CacheTrace;
use App\Models\Product;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Throwable;

// cache-aside sobre outro repositório: lê do Redis, cai no repositório interno no miss e grava o resultado
//   produto:   product:{id}, apagado no update
//   listagens: products:list:v{versão}:{categoria|all}:o{offset}:l{limit}, invalidadas trocando a versão
//              (ver:all para a listagem geral, ver:cat:{categoria} para a filtrada); as chaves antigas
//              ficam órfãs e saem pelo TTL
final class CachedProductRepository implements ProductRepository
{
    // depois de uma falha, o Redis é ignorado até o fim da requisição: uma tentativa (e um timeout) só
    private bool $unavailable = false;

    public function __construct(
        private readonly ProductRepository $inner,
        private readonly Client $redis,
        private readonly CacheTrace $trace,
        private readonly LoggerInterface $logger,
        private readonly int $ttlSeconds,
    ) {
    }

    public function find(int $id): ?Product
    {
        $key = "product:$id";
        $cached = $this->get($key);
        if ($cached !== null) {
            $this->trace->hit();

            return Product::fromRow($cached);
        }

        $this->trace->miss();
        $product = $this->inner->find($id);
        // inexistente não vai para o cache
        if ($product !== null) {
            $this->set($key, $product->toArray());
        }

        return $product;
    }

    public function list(?string $category, int $limit, int $offset): array
    {
        $version = $this->version(self::versionKey($category));
        // sem versão (Redis fora) não há chave válida: lê direto do banco
        $key = $version === null
            ? null
            : sprintf('products:list:v%s:%s:o%d:l%d', $version, $category === null ? 'all' : rawurlencode($category), $offset, $limit);

        $cached = $key === null ? null : $this->get($key);
        if ($cached !== null) {
            $this->trace->hit();

            return ['items' => array_map(Product::fromRow(...), $cached['items']), 'total' => $cached['total']];
        }

        $this->trace->miss();
        $result = $this->inner->list($category, $limit, $offset);
        if ($key !== null) {
            $this->set($key, [
                'items' => array_map(fn(Product $p) => $p->toArray(), $result['items']),
                'total' => $result['total'],
            ]);
        }

        return $result;
    }

    // o banco primeiro, depois a invalidação: apagar (e não regravar) evita gravar o valor de uma escrita concorrente perdedora
    public function update(int $id, ?int $priceCents, ?int $stock): ?Product
    {
        $product = $this->inner->update($id, $priceCents, $stock);
        if ($product === null) {
            return null;
        }

        $productKey = "product:$id";
        $versionKeys = [self::versionKey(null), self::versionKey($product->category)];
        $done = $this->attempt('invalidate', $productKey, function () use ($productKey, $versionKeys) {
            $this->redis->del($productKey);
            foreach ($versionKeys as $versionKey) {
                $this->redis->set($versionKey, self::newVersion());
            }

            return true;
        });
        // se falhou, o warning já foi logado e o TTL limita quanto tempo o dado antigo é servido
        if ($done) {
            $this->trace->invalidated([$productKey, ...$versionKeys]);
        }

        return $product;
    }

    private static function versionKey(?string $category): string
    {
        return $category === null ? 'products:ver:all' : 'products:ver:cat:' . rawurlencode($category);
    }

    // versões vêm do relógio, nunca de um contador: com allkeys-lru a chave de versão pode ser despejada,
    // e um contador recomeçando do zero reaproveitaria listagens órfãs antigas
    private static function newVersion(): string
    {
        return (string) (int) (microtime(true) * 1e6);
    }

    // a versão atual; criada na primeira leitura (SET NX: entre requisições concorrentes, vale a primeira)
    private function version(string $versionKey): ?string
    {
        return $this->attempt('version', $versionKey, function () use ($versionKey) {
            $this->redis->set($versionKey, self::newVersion(), 'NX');

            return $this->redis->get($versionKey);
        });
    }

    private function get(string $key): ?array
    {
        $raw = $this->attempt('get', $key, fn() => $this->redis->get($key));

        return $raw === null ? null : json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
    }

    private function set(string $key, array $value): void
    {
        $this->attempt('set', $key, fn() => $this->redis->setex($key, $this->ttlSeconds, json_encode($value, JSON_THROW_ON_ERROR)));
    }

    // o cache é uma otimização: uma falha vira warning no log e a requisição segue pelo banco
    private function attempt(string $operation, string $key, callable $command): mixed
    {
        if ($this->unavailable) {
            return null;
        }

        try {
            return $command();
        } catch (Throwable $e) {
            $this->unavailable = true;
            $this->logger->warning('cache unavailable', ['operation' => $operation, 'key' => $key, 'exception' => $e]);

            return null;
        }
    }
}
