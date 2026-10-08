<?php

declare(strict_types=1);

namespace App\Http;

// rotas por método + caminho; `{nome}` captura um segmento e chega ao handler em $args
final class Router
{
    /** @var array<string, array<string, callable(Request, array<string, string>): Response>> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[self::compile($pattern)][$method] = $handler;
    }

    public function dispatch(Request $req): Response
    {
        foreach ($this->routes as $regex => $handlers) {
            if (!preg_match($regex, $req->path, $matches)) {
                continue;
            }
            $handler = $handlers[$req->method] ?? null;
            if ($handler === null) {
                throw new HttpException(405, 'method not allowed', ['Allow' => implode(', ', array_keys($handlers))]);
            }
            $args = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return $handler($req, $args);
        }

        throw new HttpException(404, "route {$req->method} {$req->path} not found");
    }

    // '/products/{id}' => '#^/products/(?P<id>[^/]+)/?$#'
    private static function compile(string $pattern): string
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($pattern, '/'));

        return "#^$regex/?$#";
    }
}
