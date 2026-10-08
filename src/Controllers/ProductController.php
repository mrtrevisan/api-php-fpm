<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Models\Product;
use App\Services\ProductService;

// traduz HTTP <-> service: lê caminho, query e body; formata a resposta
final class ProductController
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function list(Request $req): Response
    {
        $page = self::intParam($req->query, 'page', 1);
        $limit = self::intParam($req->query, 'limit', 20);
        $category = $req->query['category'] ?? null;
        if ($category !== null && (!is_string($category) || $category === '')) {
            throw new HttpException(400, 'category must be a non-empty string');
        }

        $result = $this->products->list($category, $page, $limit);

        return Response::json([
            'items' => array_map(fn(Product $p) => $p->toArray(), $result['items']),
            'page' => $page,
            'limit' => $limit,
            'total' => $result['total'],
        ]);
    }

    public function show(Request $req, array $args): Response
    {
        return Response::json($this->products->get(self::id($args))->toArray());
    }

    public function update(Request $req, array $args): Response
    {
        return Response::json($this->products->update(self::id($args), $req->body)->toArray());
    }

    // id inválido é tratado como recurso inexistente
    private static function id(array $args): int
    {
        $id = $args['id'] ?? '';
        if (!ctype_digit($id) || (int) $id < 1) {
            throw new HttpException(404, 'product not found');
        }

        return (int) $id;
    }

    private static function intParam(array $query, string $name, int $default): int
    {
        $value = $query[$name] ?? null;
        if ($value === null) {
            return $default;
        }
        if (!is_string($value) || !ctype_digit($value)) {
            throw new HttpException(400, "$name must be a positive integer");
        }

        return (int) $value;
    }
}
