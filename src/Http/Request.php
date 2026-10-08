<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

final class Request
{
    // método (GET, POST, PATCH, ...), caminho após o prefixo /api,
    // query string e body JSON
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
    ) {
    }

    public static function fromGlobals(): self
    {
        // o nginx passa o caminho sem o prefixo /api em PATH_INFO
        $path = $_SERVER['PATH_INFO'] ?? '';

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $path === '' ? '/' : $path,
            $_GET,
            self::parseBody(file_get_contents('php://input') ?: ''),
        );
    }

    private static function parseBody(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        try {
            $body = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(400, 'body must be valid JSON');
        }
        if (!is_array($body)) {
            throw new HttpException(400, 'body must be a JSON object');
        }

        return $body;
    }
}
