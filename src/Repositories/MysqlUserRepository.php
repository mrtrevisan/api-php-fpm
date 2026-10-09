<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Infra\Database;
use App\Models\User;
use PDOException;

final class MysqlUserRepository implements UserRepository
{
    // ER_DUP_ENTRY: violação da chave única users_email
    private const DUPLICATE_ENTRY = 1062;

    public function __construct(private readonly Database $db)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $rows = $this->db->query('SELECT id, email, password_hash FROM users WHERE email = ?', [$email]);

        return $rows ? User::fromRow($rows[0]) : null;
    }

    // tenta inserir direto e trata a chave duplicada: checar antes teria corrida entre dois cadastros
    public function create(string $email, string $passwordHash): ?User
    {
        try {
            $this->db->execute('INSERT INTO users (email, password_hash) VALUES (?, ?)', [$email, $passwordHash]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === self::DUPLICATE_ENTRY) {
                return null;
            }
            throw $e;
        }

        return new User((int) $this->db->pdo()->lastInsertId(), $email, $passwordHash);
    }
}
