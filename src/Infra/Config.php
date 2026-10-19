<?php

declare(strict_types=1);

namespace App\Infra;

use RuntimeException;

// configuração lida do ambiente (env_file do compose)
final readonly class Config
{
    public function __construct(
        public string $dbHost,
        public int $dbPort,
        public string $dbName,
        public string $dbUser,
        public string $dbPassword,
        public string $redisHost,
        public int $redisPort,
        public string $redisPassword,
        public int $cacheTtlSeconds,
        public string $jwtSecret,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            dbHost: self::required('DB_HOST'),
            dbPort: (int) (getenv('DB_PORT') ?: 3306),
            dbName: self::required('DB_NAME'),
            dbUser: self::required('DB_USER'),
            dbPassword: self::required('DB_PASSWORD'),
            redisHost: self::required('REDIS_HOST'),
            redisPort: (int) (getenv('REDIS_PORT') ?: 6379),
            redisPassword: self::required('REDIS_PASSWORD'),
            cacheTtlSeconds: (int) (getenv('CACHE_TTL_SECONDS') ?: 600),
            jwtSecret: self::jwtSecret(),
        );
    }

    // HS256 com chave curta é quebrável por força bruta; o php-jwt também recusa menos de 32 bytes
    private static function jwtSecret(): string
    {
        $secret = self::required('JWT_SECRET_KEY');
        if (strlen($secret) < 32) {
            throw new RuntimeException('environment variable JWT_SECRET_KEY must have at least 32 bytes');
        }

        return $secret;
    }

    private static function required(string $name): string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            throw new RuntimeException("environment variable $name is not set");
        }

        return $value;
    }
}
