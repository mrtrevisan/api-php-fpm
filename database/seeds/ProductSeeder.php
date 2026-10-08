<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

// 500 produtos sintéticos em 10 categorias; recria do zero a cada execução
final class ProductSeeder extends AbstractSeed
{
    private const TOTAL = 500;
    private const CATEGORIES = 10;

    public function run(): void
    {
        $products = $this->table('products');
        $products->truncate();

        $rows = [];
        for ($n = 1; $n <= self::TOTAL; $n++) {
            $rows[] = [
                'sku' => sprintf('SKU-%06d', $n),
                'name' => sprintf('Product %06d', $n),
                'category' => sprintf('category-%02d', $n % self::CATEGORIES + 1),
                'price_cents' => 100 + ($n * 7919) % 99900,
                'stock' => ($n * 104729) % 500,
            ];
        }
        $products->insert($rows)->saveData();
    }
}
