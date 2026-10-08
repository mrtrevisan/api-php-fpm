<?php

declare(strict_types=1);

namespace App\Infra;

use Predis\Client;

final class RedisFactory
{
    // o Predis só conecta no primeiro comando
    public static function create(Config $config): Client
    {
        return new Client([
            'host' => $config->redisHost,
            'port' => $config->redisPort,
            'password' => $config->redisPassword,
            // cache fora do ar não pode travar a requisição
            'timeout' => 1.0,
            'read_write_timeout' => 1.0,
        ]);
    }
}
