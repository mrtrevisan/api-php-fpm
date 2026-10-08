<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Controllers\ProductController;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Infra\Config;
use App\Infra\Database;
use App\Infra\RedisFactory;
use App\Repositories\MysqlProductRepository;
use App\Services\ProductService;

require __DIR__ . '/../vendor/autoload.php';

// warnings e notices viram exceções, tratadas abaixo como 500
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // montagem das dependências: controller -> service -> repository -> infra
    $config = Config::fromEnv();
    $db = new Database($config);
    $redis = RedisFactory::create($config);

    $products = new ProductController(new ProductService(new MysqlProductRepository($db)));
    $health = new HealthController($db, $redis);

    $router = new Router();
    $router->get('/health', $health->check(...));
    $router->get('/products', $products->list(...));
    $router->get('/products/{id}', $products->show(...));
    $router->patch('/products/{id}', $products->update(...));

    $response = $router->dispatch(Request::fromGlobals());
} catch (HttpException $e) {
    $response = Response::json(['error' => $e->getMessage()], $e->status, $e->headers);
} catch (Throwable $e) {
    // detalhes vão para o log do container, nunca para o cliente
    error_log((string) $e);
    $response = Response::json(['error' => 'internal server error'], 500);
}

$response->send();
