<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Controllers\ProductController;
use App\Http\Router;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\ResellerMiddleware;

/** @var Router $router */

// ---------------- عمومی ----------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->post('/contact', [HomeController::class, 'contactSubmit']);
$router->get('/terms', [HomeController::class, 'terms']);
$router->get('/privacy', [HomeController::class, 'privacy']);
$router->get('/faq', [HomeController::class, 'faq']);
$router->get('/phones/{id}', [ProductController::class, 'number']);
$router->get('/modems/{id}', [ProductController::class, 'modem']);

// ---------------- سبد خرید ----------------
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add-number', [CartController::class, 'addNumber']);
$router->post('/cart/add-modem', [CartController::class, 'addModem']);
$router->post('/cart/add-activation', [CartController::class, 'addActivation']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);

// ---------------- پرداخت ----------------
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'store']);
$router->post('/payment/start', [PaymentController::class, 'start']);
$router->get('/payment/callback/{gateway}', [PaymentController::class, 'callback'], ['csrf' => false]);
$router->post('/payment/callback/{gateway}', [PaymentController::class, 'callback'], ['csrf' => false]);
$router->get('/order/{tracking}', [OrderController::class, 'show']);

// ---------------- ورود / ثبت‌نام ----------------
$router->get('/login', [AuthController::class, 'showLogin'], ['middleware' => [GuestMiddleware::class]]);
$router->post('/login', [AuthController::class, 'login'], ['middleware' => [GuestMiddleware::class]]);
$router->get('/reseller/login', [AuthController::class, 'showResellerLogin'], ['middleware' => [GuestMiddleware::class]]);
$router->post('/reseller/login', [AuthController::class, 'loginReseller'], ['middleware' => [GuestMiddleware::class]]);
$router->get('/admin/login', [AuthController::class, 'showAdminLogin'], ['middleware' => [GuestMiddleware::class]]);
$router->post('/admin/login', [AuthController::class, 'loginAdmin'], ['middleware' => [GuestMiddleware::class]]);
$router->get('/login/2fa', [AuthController::class, 'showTwoFactor']);
$router->post('/login/2fa', [AuthController::class, 'verifyTwoFactor']);
$router->get('/register', [AuthController::class, 'showRegister'], ['middleware' => [GuestMiddleware::class]]);
$router->post('/register', [AuthController::class, 'register'], ['middleware' => [GuestMiddleware::class]]);
$router->post('/logout', [AuthController::class, 'logout'], ['middleware' => [AuthMiddleware::class]]);

// ---------------- پنل نماینده ----------------
$router->group(['prefix' => '/reseller', 'middleware' => [ResellerMiddleware::class]], function (Router $router): void {
    $router->get('/', [\App\Controllers\Reseller\DashboardController::class, 'index']);

    $router->get('/numbers', [\App\Controllers\Reseller\NumberController::class, 'index']);
    $router->get('/numbers/create', [\App\Controllers\Reseller\NumberController::class, 'create']);
    $router->post('/numbers/store', [\App\Controllers\Reseller\NumberController::class, 'store']);
    $router->get('/numbers/{id}/edit', [\App\Controllers\Reseller\NumberController::class, 'edit']);
    $router->post('/numbers/{id}/update', [\App\Controllers\Reseller\NumberController::class, 'update']);
    $router->post('/numbers/{id}/delete', [\App\Controllers\Reseller\NumberController::class, 'delete']);

    $router->get('/modems', [\App\Controllers\Reseller\ModemController::class, 'index']);
    $router->get('/modems/create', [\App\Controllers\Reseller\ModemController::class, 'create']);
    $router->post('/modems/store', [\App\Controllers\Reseller\ModemController::class, 'store']);
    $router->get('/modems/{id}/edit', [\App\Controllers\Reseller\ModemController::class, 'edit']);
    $router->post('/modems/{id}/update', [\App\Controllers\Reseller\ModemController::class, 'update']);
    $router->post('/modems/{id}/delete', [\App\Controllers\Reseller\ModemController::class, 'delete']);

    $router->get('/import', [\App\Controllers\Reseller\ImportController::class, 'index']);
    $router->post('/import/upload', [\App\Controllers\Reseller\ImportController::class, 'upload']);
    $router->get('/import/{id}', [\App\Controllers\Reseller\ImportController::class, 'show']);
    $router->post('/import/{id}/process', [\App\Controllers\Reseller\ImportController::class, 'process']);
    $router->get('/import/template/{type}', [\App\Controllers\Reseller\ImportController::class, 'template']);

    $router->get('/sales', [\App\Controllers\Reseller\SalesController::class, 'index']);
    $router->get('/sales/export', [\App\Controllers\Reseller\SalesController::class, 'export']);
});

// ---------------- پنل ادمین ----------------
$router->group(['prefix' => '/admin', 'middleware' => [AdminMiddleware::class]], function (Router $router): void {
    $router->get('/', [\App\Controllers\Admin\DashboardController::class, 'index']);

    $router->get('/import', [\App\Controllers\Admin\ImportController::class, 'index']);
    $router->post('/import/upload', [\App\Controllers\Admin\ImportController::class, 'upload']);
    $router->get('/import/template/{type}', [\App\Controllers\Admin\ImportController::class, 'template']);
    $router->get('/import/{id}', [\App\Controllers\Admin\ImportController::class, 'show']);
    $router->post('/import/{id}/process', [\App\Controllers\Admin\ImportController::class, 'process']);

    $router->get('/numbers', [\App\Controllers\Admin\NumberController::class, 'index']);
    $router->get('/numbers/create', [\App\Controllers\Admin\NumberController::class, 'create']);
    $router->post('/numbers/store', [\App\Controllers\Admin\NumberController::class, 'store']);
    $router->get('/numbers/{id}/edit', [\App\Controllers\Admin\NumberController::class, 'edit']);
    $router->post('/numbers/{id}/update', [\App\Controllers\Admin\NumberController::class, 'update']);
    $router->post('/numbers/{id}/delete', [\App\Controllers\Admin\NumberController::class, 'delete']);

    $router->get('/modems', [\App\Controllers\Admin\ModemController::class, 'index']);
    $router->get('/modems/create', [\App\Controllers\Admin\ModemController::class, 'create']);
    $router->post('/modems/store', [\App\Controllers\Admin\ModemController::class, 'store']);
    $router->get('/modems/{id}/edit', [\App\Controllers\Admin\ModemController::class, 'edit']);
    $router->post('/modems/{id}/update', [\App\Controllers\Admin\ModemController::class, 'update']);
    $router->post('/modems/{id}/delete', [\App\Controllers\Admin\ModemController::class, 'delete']);

    $router->get('/activation', [\App\Controllers\Admin\ActivationController::class, 'index']);
    $router->post('/activation', [\App\Controllers\Admin\ActivationController::class, 'update']);
    $router->post('/activation/operators', [\App\Controllers\Admin\ActivationController::class, 'storeOperator']);

    $router->get('/orders', [\App\Controllers\Admin\OrderController::class, 'index']);
    $router->get('/orders/export', [\App\Controllers\Admin\OrderController::class, 'export']);
    $router->get('/orders/{id}', [\App\Controllers\Admin\OrderController::class, 'show']);
    $router->post('/orders/{id}/status', [\App\Controllers\Admin\OrderController::class, 'updateStatus']);

    $router->get('/resellers', [\App\Controllers\Admin\UserController::class, 'index']);
    $router->get('/resellers/create', [\App\Controllers\Admin\UserController::class, 'create']);
    $router->post('/resellers/store', [\App\Controllers\Admin\UserController::class, 'store']);
    $router->get('/resellers/{id}/edit', [\App\Controllers\Admin\UserController::class, 'edit']);
    $router->post('/resellers/{id}/update', [\App\Controllers\Admin\UserController::class, 'update']);

    $router->get('/reports', [\App\Controllers\Admin\ReportController::class, 'index']);
    $router->get('/reports/export', [\App\Controllers\Admin\ReportController::class, 'export']);

    $router->get('/sms', [\App\Controllers\Admin\SmsController::class, 'index']);
    $router->post('/sms/{id}/retry', [\App\Controllers\Admin\SmsController::class, 'retry']);

    $router->get('/messages', [\App\Controllers\Admin\MessageController::class, 'index']);
    $router->post('/messages/{id}/read', [\App\Controllers\Admin\MessageController::class, 'markRead']);

    $router->get('/settings', [\App\Controllers\Admin\SettingController::class, 'index']);
    $router->post('/settings', [\App\Controllers\Admin\SettingController::class, 'update']);
    $router->get('/settings/2fa', [\App\Controllers\Admin\SettingController::class, 'twoFactor']);
    $router->post('/settings/2fa', [\App\Controllers\Admin\SettingController::class, 'twoFactorAction']);

    $router->get('/audit', [\App\Controllers\Admin\AuditController::class, 'index']);
});
