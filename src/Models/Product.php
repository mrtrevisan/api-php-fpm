<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public string $category,
        // preço em centavos, para não usar float com dinheiro
        public int $priceCents,
        public int $stock,
        public string $updatedAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            $row['sku'],
            $row['name'],
            $row['category'],
            (int) $row['price_cents'],
            (int) $row['stock'],
            $row['updated_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category,
            'price_cents' => $this->priceCents,
            'stock' => $this->stock,
            'updated_at' => $this->updatedAt,
        ];
    }
}
