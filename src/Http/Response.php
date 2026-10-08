<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public function __construct(
        public readonly int $status,
        public readonly array $data,
        public readonly array $headers = [],
    ) {
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self($status, $data, $headers);
    }

    public function send(): void
    {
        // serializa antes de enviar os headers, para uma falha ainda poder virar 500
        $body = json_encode($this->data, JSON_THROW_ON_ERROR);

        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $body;
    }
}
