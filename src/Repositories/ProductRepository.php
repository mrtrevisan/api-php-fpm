<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;

// contrato do acesso a produtos; uma implementação com cache pode envolver a do MySQL
interface ProductRepository
{
    public function find(int $id): ?Product;

    /** @return array{items: list<Product>, total: int} */
    public function list(?string $category, int $limit, int $offset): array;

    // altera só os campos informados (null = mantém); retorna o produto atualizado
    public function update(int $id, ?int $priceCents, ?int $stock): ?Product;
}
