<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

// erro esperado, com status HTTP e mensagem que podem ir para o cliente
final class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }
}
