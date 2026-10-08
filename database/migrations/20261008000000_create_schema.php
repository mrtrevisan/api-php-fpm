<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

// migration zero: o schema inicial do catálogo
final class CreateSchema extends AbstractMigration
{
    public function change(): void
    {
        $this->table('products', [
            'id' => false,
            'primary_key' => 'id',
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_0900_ai_ci',
        ])
            ->addColumn('id', 'biginteger', ['signed' => false, 'identity' => true])
            ->addColumn('sku', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('category', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('price_cents', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('stock', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('updated_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex('sku', ['unique' => true, 'name' => 'products_sku'])
            // listagem por categoria, ordenada por nome
            ->addIndex(['category', 'name'], ['name' => 'products_category_name'])
            ->create();
    }
}
