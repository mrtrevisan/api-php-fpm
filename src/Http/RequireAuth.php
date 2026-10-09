<?php

declare(strict_types=1);

namespace App\Http;

use App\Services\AuthService;
use Closure;

// envolve o handler de uma rota: só chega nele com um token válido, e com $req->userId preenchido
final class RequireAuth
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function __invoke(callable $handler): Closure
    {
        return function (Request $req, array $args) use ($handler): Response {
            $userId = $this->auth->authenticate($req->authorization);

            return $handler($req->withUserId($userId), $args);
        };
    }
}
