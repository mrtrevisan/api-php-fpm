<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Infra\Database;
use Predis\Client;
use Throwable;

// verifica as dependências: 200 se todas respondem, 503 se alguma falha
final class HealthController
{
    public function __construct(
        private readonly Database $db,
        private readonly Client $redis,
    ) {
    }

    public function check(Request $req): Response
    {
        $checks = [
            'mysql' => self::probe(fn() => $this->db->query('SELECT 1')),
            'redis' => self::probe(fn() => $this->redis->ping()),
        ];
        $healthy = !in_array('error', $checks, true);

        return Response::json(
            ['status' => $healthy ? 'ok' : 'error', 'checks' => $checks],
            $healthy ? 200 : 503,
        );
    }

    private static function probe(callable $check): string
    {
        try {
            $check();

            return 'ok';
        } catch (Throwable $e) {
            // o motivo vai para o log; o cliente só vê o estado
            error_log((string) $e);

            return 'error';
        }
    }
}
