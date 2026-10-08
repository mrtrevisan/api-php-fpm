<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Infra\Database;
use App\Models\Product;

final class MysqlProductRepository implements ProductRepository
{
    private const COLUMNS = 'id, sku, name, category, price_cents, stock, updated_at';

    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?Product
    {
        $rows = $this->db->query('SELECT ' . self::COLUMNS . ' FROM products WHERE id = ?', [$id]);

        return $rows ? Product::fromRow($rows[0]) : null;
    }

    public function list(?string $category, int $limit, int $offset): array
    {
        $where = $category === null ? '' : 'WHERE category = ?';
        $params = $category === null ? [] : [$category];

        // limit e offset são int validados pelo service; interpolar evita o bind de string no LIMIT
        $rows = $this->db->query(
            'SELECT ' . self::COLUMNS . " FROM products $where ORDER BY name, id LIMIT $limit OFFSET $offset",
            $params,
        );
        $total = $this->db->query("SELECT COUNT(*) AS total FROM products $where", $params)[0]['total'];

        return [
            'items' => array_map(Product::fromRow(...), $rows),
            'total' => (int) $total,
        ];
    }

    public function update(int $id, ?int $priceCents, ?int $stock): ?Product
    {
        $set = [];
        $params = [];
        if ($priceCents !== null) {
            $set[] = 'price_cents = ?';
            $params[] = $priceCents;
        }
        if ($stock !== null) {
            $set[] = 'stock = ?';
            $params[] = $stock;
        }

        if ($set) {
            $params[] = $id;
            $this->db->execute('UPDATE products SET ' . implode(', ', $set) . ' WHERE id = ?', $params);
        }

        // rowCount é 0 também quando os valores não mudam, então o retorno vem de uma nova leitura
        return $this->find($id);
    }
}
