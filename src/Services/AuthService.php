<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\HttpException;
use App\Models\User;
use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

// cadastro, login e validação de tokens JWT (HS256, `sub` = id do usuário)
final class AuthService
{
    private const ALGORITHM = 'HS256';
    private const TOKEN_TTL_SECONDS = 3600;
    private const PASSWORD_MIN_LENGTH = 8;
    // RFC 5321
    private const EMAIL_MAX_LENGTH = 254;

    public function __construct(
        private readonly UserRepository $users,
        private readonly string $jwtSecret,
    ) {
    }

    public function register(mixed $email, mixed $password): User
    {
        $email = self::normalizeEmail($email);
        if (strlen($email) > self::EMAIL_MAX_LENGTH || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new HttpException(400, 'email is invalid');
        }
        if (!is_string($password) || mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            throw new HttpException(400, 'password must have at least ' . self::PASSWORD_MIN_LENGTH . ' characters');
        }

        return $this->users->create($email, password_hash($password, PASSWORD_ARGON2ID))
            ?? throw new HttpException(409, 'email already registered');
    }

    // o login não aplica as regras do cadastro: qualquer entrada errada é só credencial inválida
    public function login(mixed $email, mixed $password): string
    {
        $email = self::normalizeEmail($email);
        if ($email === '' || !is_string($password)) {
            throw new HttpException(401, 'invalid credentials');
        }

        $user = $this->users->findByEmail($email);
        // email desconhecido também verifica um hash, para as duas respostas levarem o mesmo tempo
        $valid = password_verify($password, $user?->passwordHash ?? self::dummyHash());
        if ($user === null || !$valid) {
            throw new HttpException(401, 'invalid credentials');
        }

        $now = time();

        return JWT::encode(
            ['sub' => (string) $user->id, 'iat' => $now, 'exp' => $now + self::TOKEN_TTL_SECONDS],
            $this->jwtSecret,
            self::ALGORITHM,
        );
    }

    // valor do header Authorization -> id do usuário; token ausente, inválido ou expirado é 401
    public function authenticate(?string $authorization): int
    {
        // o esquema é case-insensitive (RFC 9110)
        if ($authorization === null || !preg_match('/^Bearer +(\S+)$/i', $authorization, $matches)) {
            throw self::unauthorized('token not found');
        }

        try {
            $payload = JWT::decode($matches[1], new Key($this->jwtSecret, self::ALGORITHM));
        } catch (Throwable) {
            throw self::unauthorized('invalid token');
        }

        $sub = $payload->sub ?? null;
        if (!is_string($sub) || !ctype_digit($sub)) {
            throw self::unauthorized('invalid token');
        }

        return (int) $sub;
    }

    private static function normalizeEmail(mixed $value): string
    {
        return is_string($value) ? strtolower(trim($value)) : '';
    }

    private static function unauthorized(string $message): HttpException
    {
        return new HttpException(401, $message, ['WWW-Authenticate' => 'Bearer']);
    }

    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID);
    }
}
