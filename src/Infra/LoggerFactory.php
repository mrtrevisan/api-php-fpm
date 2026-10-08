<?php

declare(strict_types=1);

namespace App\Infra;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    // uma linha JSON por evento no stderr: o FPM repassa para o log do container (docker logs)
    // não depende de Config: precisa funcionar mesmo quando a configuração falha
    public static function create(): LoggerInterface
    {
        $level = Level::fromName(getenv('LOG_LEVEL') ?: 'info');

        $handler = new StreamHandler('php://stderr', $level);
        $formatter = new JsonFormatter();
        // erros inesperados (500) só são investigáveis com o trace
        $formatter->includeStacktraces();
        $handler->setFormatter($formatter);

        // o uid é gerado por instância, ou seja, um por requisição: correlaciona as linhas
        return new Logger('mystock', [$handler], [new UidProcessor()]);
    }
}
