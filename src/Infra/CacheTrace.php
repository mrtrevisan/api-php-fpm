<?php

declare(strict_types=1);

namespace App\Infra;

// o que o cache fez nesta requisição; vira o header X-Cache e campos da linha de log
// no FPM cada requisição monta o seu, então não há estado compartilhado entre requisições
final class CacheTrace
{
    private ?string $status = null;

    /** @var list<string> */
    private array $invalidated = [];

    public function hit(): void
    {
        $this->status = 'hit';
    }

    public function miss(): void
    {
        $this->status = 'miss';
    }

    /** @param list<string> $keys */
    public function invalidated(array $keys): void
    {
        $this->invalidated = [...$this->invalidated, ...$keys];
    }

    // HIT ou MISS; null quando a requisição não leu do cache
    public function header(): ?string
    {
        return $this->status === null ? null : strtoupper($this->status);
    }

    public function logContext(): array
    {
        return array_filter(['cache' => $this->status, 'invalidated' => $this->invalidated ?: null]);
    }
}
