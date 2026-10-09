<?php

declare(strict_types=1);

namespace App\Models;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $passwordHash,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self((int) $row['id'], $row['email'], $row['password_hash']);
    }

    // o hash nunca sai da API
    public function toArray(): array
    {
        return ['id' => $this->id, 'email' => $this->email];
    }
}
