<?php

declare(strict_types=1);

namespace App\Infra;

use PDO;

final class Database
{
    private ?PDO $pdo = null;

    public function __construct(private readonly Config $config)
    {
    }

    // uma conexão por requisição, aberta no primeiro uso
    public function pdo(): PDO
    {
        return $this->pdo ??= new PDO(
            "mysql:host={$this->config->dbHost};port={$this->config->dbPort};dbname={$this->config->dbName};charset=utf8mb4",
            $this->config->dbUser,
            $this->config->dbPassword,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }

    // o SQL é fixo, escrito por quem chama; valores sempre via placeholders
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // INSERT/UPDATE/DELETE; retorna o número de linhas afetadas
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }
}
