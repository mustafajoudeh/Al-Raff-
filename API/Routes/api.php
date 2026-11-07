<?php
declare(strict_types=1);

use App\Enum\User\Role;

return function (Router $router): void {

    $admin = [AuthMiddleware::RoleIs(Role::Admin)];

    $router->get('/health', function (Request $request) {
        Response::json(200, true, 'Al-Raff API is running', ['status' => 'OK']);
    });

    $router->post('/auth/login', [AuthController::class, 'Login']);
    $router->get('/auth/me', [AuthController::class, 'Me'], [AuthMiddleware::Authenticated()]);
    $router->post('/auth/logout', [AuthController::class, 'Logout'], [AuthMiddleware::Authenticated()]);

    $router->get('/admin/customers', [AdminController::class, 'Customers'], $admin);
    $router->get('/admin/admins', [AdminController::class, 'Admins'], $admin);
    $router->get('/admin/users/{id}', [AdminController::class, 'GetUser'], $admin);
    $router->post('/admin/users', [AdminController::class, 'AddUser'], $admin);
    $router->put('/admin/users/{id}', [AdminController::class, 'UpdateUser'], $admin);
    $router->delete('/admin/users/{id}', [AdminController::class, 'DeleteUser'], $admin);
    $router->patch('/admin/users/{id}/activate', [AdminController::class, 'ActivateUser'], $admin);
    $router->patch('/admin/users/{id}/deactivate', [AdminController::class, 'DeActivateUser'], $admin);
    $router->patch('/admin/users/{id}/password', [AdminController::class, 'ChangePassword'], $admin);

    // Categories - Public
    $router->get('/categories', [CategoryController::class, 'GetAllCategories']);
    $router->get('/categories/{id}', [CategoryController::class, 'GetCategory']);

    // Products - Public
    $router->get('/products', [ProductController::class, 'GetAllProducts']);
    $router->get('/products/{id}', [ProductController::class, 'GetProduct']);
    $router->get('/categories/{categoryId}/products', [ProductController::class, 'GetProductsByCategory']);

    // Categories - Admin
    $router->post('/admin/categories', [CategoryController::class, 'AddCategory'], $admin);
    $router->put('/admin/categories/{id}', [CategoryController::class, 'UpdateCategory'], $admin);
    $router->delete('/admin/categories/{id}', [CategoryController::class, 'DeleteCategory'], $admin);

    // Products - Admin
    $router->post('/admin/products', [ProductController::class, 'AddProduct'], $admin);
    $router->put('/admin/products/{id}', [ProductController::class, 'UpdateProduct'], $admin);
    $router->delete('/admin/products/{id}', [ProductController::class, 'DeleteProduct'], $admin);
};