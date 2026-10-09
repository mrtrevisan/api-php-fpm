<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    // null quando o email já está cadastrado
    public function create(string $email, string $passwordHash): ?User;
}
