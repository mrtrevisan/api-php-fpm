<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthService;

// os valores do body chegam sem validação; o service confere
final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function register(Request $req): Response
    {
        $user = $this->auth->register($req->body['email'] ?? null, $req->body['password'] ?? null);

        return Response::json($user->toArray(), 201);
    }

    public function login(Request $req): Response
    {
        return Response::json(['token' => $this->auth->login($req->body['email'] ?? null, $req->body['password'] ?? null)]);
    }
}
