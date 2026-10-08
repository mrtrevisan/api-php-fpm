<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\HttpException;
use App\Models\Product;
use App\Repositories\ProductRepository;

final class ProductService
{
    public const MAX_LIMIT = 100;

    public function __construct(private readonly ProductRepository $products)
    {
    }

    public function get(int $id): Product
    {
        return $this->products->find($id) ?? throw new HttpException(404, 'product not found');
    }

    /** @return array{items: list<Product>, total: int} */
    public function list(?string $category, int $page, int $limit): array
    {
        if ($page < 1) {
            throw new HttpException(400, 'page must be at least 1');
        }
        if ($limit < 1 || $limit > self::MAX_LIMIT) {
            throw new HttpException(400, 'limit must be between 1 and ' . self::MAX_LIMIT);
        }

        return $this->products->list($category, $limit, ($page - 1) * $limit);
    }

    // aceita price_cents e/ou stock, inteiros não negativos
    public function update(int $id, array $changes): Product
    {
        $priceCents = self::nonNegativeInt($changes, 'price_cents');
        $stock = self::nonNegativeInt($changes, 'stock');
        if ($priceCents === null && $stock === null) {
            throw new HttpException(400, 'send price_cents and/or stock');
        }

        return $this->products->update($id, $priceCents, $stock) ?? throw new HttpException(404, 'product not found');
    }

    private static function nonNegativeInt(array $changes, string $field): ?int
    {
        if (!array_key_exists($field, $changes)) {
            return null;
        }
        $value = $changes[$field];
        if (!is_int($value) || $value < 0) {
            throw new HttpException(400, "$field must be a non-negative integer");
        }

        return $value;
    }
}
