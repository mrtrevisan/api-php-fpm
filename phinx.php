<?php

declare(strict_types=1);

// mesmo ambiente da API (env_file do compose); rode dentro do container local-mystock-fpm
return [
    'paths' => [
        'migrations' => __DIR__ . '/database/migrations',
        'seeds' => __DIR__ . '/database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'default',
        'default' => [
            'adapter' => 'mysql',
            'host' => getenv('DB_HOST'),
            'port' => (int) (getenv('DB_PORT') ?: 3306),
            'name' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'pass' => getenv('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_0900_ai_ci',
        ],
    ],
    'version_order' => 'creation',
];
