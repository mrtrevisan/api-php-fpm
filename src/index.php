<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\ProductController;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\RequireAuth;
use App\Http\Response;
use App\Http\Router;
use App\Infra\Config;
use App\Infra\Database;
use App\Infra\LoggerFactory;
use App\Infra\RedisFactory;
use App\Repositories\MysqlProductRepository;
use App\Repositories\MysqlUserRepository;
use App\Services\AuthService;
use App\Services\ProductService;

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

try {
    // montagem das dependências: controller -> service -> repository -> infra
    $config = Config::fromEnv();
    $db = new Database($config);
    $redis = RedisFactory::create($config);

    $authService = new AuthService(new MysqlUserRepository($db), $config->jwtSecret);
    $requireAuth = new RequireAuth($authService);

    $auth = new AuthController($authService);
    $products = new ProductController(new ProductService(new MysqlProductRepository($db)));
    $health = new HealthController($db, $redis, $logger);

    $router = new Router();
    $router->get('/health', $health->check(...));
    $router->post('/auth/register', $auth->register(...));
    $router->post('/auth/login', $auth->login(...));
    $router->get('/products', $products->list(...));
    $router->get('/products/{id}', $products->show(...));
    // leituras são públicas; alterações exigem token
    $router->patch('/products/{id}', $requireAuth($products->update(...)));

    $response = $router->dispatch(Request::fromGlobals());
} catch (HttpException $e) {
    $response = Response::json(['error' => $e->getMessage()], $e->status, $e->headers);
} catch (Throwable $e) {
    // detalhes vão para o log do container, nunca para o cliente
    $logger->error('unhandled exception', ['exception' => $e]);
    $response = Response::json(['error' => 'internal server error'], 500);
}

$response->send();

// uma linha por requisição, depois da resposta montada
$logger->info('request', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'uri' => $_SERVER['REQUEST_URI'] ?? null,
    'status' => $response->status,
    'duration_ms' => round((hrtime(true) - $startedAt) / 1e6, 2),
]);
