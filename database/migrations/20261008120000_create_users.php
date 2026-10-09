<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

// usuários da API, para autenticação com JWT
final class CreateUsers extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users', [
            'id' => false,
            'primary_key' => 'id',
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_0900_ai_ci',
        ])
            ->addColumn('id', 'integer', ['signed' => false, 'identity' => true])
            // RFC 5321: até 254 caracteres; salvo já normalizado (trim + minúsculas)
            ->addColumn('email', 'string', ['limit' => 254, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('email', ['unique' => true, 'name' => 'users_email'])
            ->create();
    }
}
