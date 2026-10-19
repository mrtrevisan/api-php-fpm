<?php

declare(strict_types=1);

use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Infra\CacheTrace;
use App\Infra\LoggerFactory;

// ciclo de vida da requisição: tratamento de erros, resposta e linha de log
// a montagem das camadas e as rotas ficam em app.php

require __DIR__ . '/../vendor/autoload.php';

// warnings e notices viram exceções, tratadas abaixo como 500
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$startedAt = hrtime(true);
$logger = LoggerFactory::create();
$cacheTrace = new CacheTrace();

try {
    $createRouter = require __DIR__ . '/app.php';
    $router = $createRouter($logger, $cacheTrace);

    $response = $router->dispatch(Request::fromGlobals());
} catch (HttpException $e) {
    $response = Response::json(['error' => $e->getMessage()], $e->status, $e->headers);
} catch (Throwable $e) {
    // detalhes vão para o log do container, nunca para o cliente
    $logger->error('unhandled exception', ['exception' => $e]);
    $response = Response::json(['error' => 'internal server error'], 500);
}

// HIT/MISS só nas requisições que leram do cache
$cacheHeader = $cacheTrace->header();
if ($cacheHeader !== null) {
    $response = $response->withHeader('X-Cache', $cacheHeader);
}

$response->send();

// uma linha por requisição, depois da resposta montada
$logger->info('request', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'uri' => $_SERVER['REQUEST_URI'] ?? null,
    'status' => $response->status,
    'duration_ms' => round((hrtime(true) - $startedAt) / 1e6, 2),
    ...$cacheTrace->logContext(),
]);
