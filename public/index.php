<?php

declare(strict_types=1);
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/src/Core/App.php';
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

use Controllers\CategoryController;
use Controllers\HomeController;
use Controllers\PostController;
use Controllers\UserController;

$dispatcher = FastRoute\cachedDispatcher(function (FastRoute\RouteCollector $r) {
    $r->addGroup(
        '/',
        function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '', HomeController::class . '/me');
            $r->addRoute('POST', 'login', HomeController::class . '/login');
            $r->addRoute('POST', 'register', HomeController::class . '/register');
            $r->addRoute('POST', 'refresh', HomeController::class . '/refresh');
            $r->addRoute('POST', 'logout', HomeController::class . '/logout');
            $r->addRoute('POST', 'change-password', HomeController::class . '/update_password');
        }
    );
    $r->addGroup(
        '/posts',
        function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '', PostController::class . '/index');
            $r->addRoute('GET', '/[{slug}]', PostController::class . '/slug');
            $r->addRoute('POST', '', PostController::class . '/create');
            $r->addRoute('PATCH', '/{id:\d+}', PostController::class . '/update');
            $r->addRoute('DELETE', '/{id:\d+}', PostController::class . '/delete');
        }
    );
    $r->addGroup(
        '/categories',
        function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '', CategoryController::class . '/index');
            $r->addRoute('GET', '/[{slug}]', CategoryController::class . '/slug');
            $r->addRoute('POST', '', CategoryController::class . '/create');
            $r->addRoute('PATCH', '/{id:\d+}', CategoryController::class . '/update');
            $r->addRoute('DELETE', '/{id:\d+}', CategoryController::class . '/delete');
        }
    );
    $r->addGroup(
        '/users',
        function (\FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '', UserController::class . '/index');
            $r->addRoute('GET', '/[{username}]', UserController::class . '/username');
        }
    );
}, [
    'cacheFile' => __DIR__ . '/route.cache',
]);

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = str_replace('/api', '', $_SERVER['REQUEST_URI']);

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode([
            'status' => 404,
            'errors' => 'Page not found',
        ]);

        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo json_encode([
            'status' => 405,
            'errors' => 'Method not allowed',
        ]);

        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        list($class, $method) = explode('/', $handler, 2);
        call_user_func_array([new $class, $method], $vars);

        break;
}
