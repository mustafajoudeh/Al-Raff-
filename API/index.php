<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Router.php';

require_once __DIR__ . '/../Enum/Mode.php';
require_once __DIR__ . '/../Enum/User/Role.php';
require_once __DIR__ . '/../Enum/User/Status.php';

require_once __DIR__ . '/Middleware/AuthMiddleware.php';

require_once __DIR__ . '/Controller/AuthController.php';
require_once __DIR__ . '/Controller/AdminController.php';
require_once __DIR__ . '/Controller/CategoryController.php';
require_once __DIR__ . '/Controller/ProductController.php';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => false,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

try {

    $request = Request::capture();

    $router = new Router();

    $registerRoutes = require __DIR__ . '/Routes/api.php';

    $registerRoutes($router);

    $router->dispatch($request);

} catch (JsonException $e) {

    Response::json(
        400,
        false,
        'Invalid JSON format'
    );

} catch (UnexpectedValueException $e) {

    Response::json(
        400,
        false,
        'Invalid request body'
    );

} catch (Throwable $e) {

    error_log($e->getMessage());

    Response::json(
        500,
        false,
        'Internal Server Error'
    );

}