<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\ProductController;
use App\Http\RequireAuth;
use App\Http\Router;
use App\Infra\CacheTrace;
use App\Infra\Config;
use App\Infra\Database;
use App\Infra\RedisFactory;
use App\Repositories\CachedProductRepository;
use App\Repositories\MysqlProductRepository;
use App\Repositories\MysqlUserRepository;
use App\Services\AuthService;
use App\Services\ProductService;
use Psr\Log\LoggerInterface;

// montagem da aplicação: dependências (controller -> service -> repository -> infra) e rotas
// devolve uma função para que o index.php a chame dentro do try: falhas de configuração viram 500 logado
return function (LoggerInterface $logger, CacheTrace $cacheTrace): Router {
    $config = Config::fromEnv();
    $db = new Database($config);
    $redis = RedisFactory::create($config);

    $authService = new AuthService(new MysqlUserRepository($db), $config->jwtSecret);
    $requireAuth = new RequireAuth($authService);

    $auth = new AuthController($authService);
    $productRepository = new CachedProductRepository(
        new MysqlProductRepository($db),
        $redis,
        $cacheTrace,
        $logger,
        $config->cacheTtlSeconds,
    );
    $products = new ProductController(new ProductService($productRepository));
    $health = new HealthController($db, $redis, $logger);

    $router = new Router();
    $router->get('/health', $health->check(...));
    $router->post('/auth/register', $auth->register(...));
    $router->post('/auth/login', $auth->login(...));
    $router->get('/products', $products->list(...));
    $router->get('/products/{id}', $products->show(...));
    // leituras são públicas; alterações exigem token
    $router->patch('/products/{id}', $requireAuth($products->update(...)));

    return $router;
};
