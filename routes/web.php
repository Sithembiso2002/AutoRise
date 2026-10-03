<?php
declare(strict_types=1);

use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\OrderController    as AdminOrderController;
use App\Controllers\Admin\ProductController  as AdminProductController;
use App\Controllers\Admin\ReportController   as AdminReportController;
use App\Controllers\Admin\UserController     as AdminUserController;
use App\Controllers\Auth\ForgotPasswordController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\LogoutController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\Auth\ResetPasswordController;
use App\Controllers\Shop\AboutController;
use App\Controllers\Shop\CartController;
use App\Controllers\Shop\CheckoutController;
use App\Controllers\Shop\HomeController;
use App\Controllers\Shop\OrderController;
use App\Controllers\Shop\PaymentController;
use App\Controllers\Shop\PaymentWebhookController;
use App\Controllers\Shop\ProductController;
use App\Controllers\Shop\ProfileController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RoleMiddleware;

return static function (Router $r): void {

    /* ============================================================
       PUBLIC SHOP
       ============================================================ */
    $r->get('/',              [HomeController::class,    'index']);
    $r->get('/products',      [ProductController::class, 'index']);
    $r->get('/products/{id}', [ProductController::class, 'show']);
    $r->get('/about',         [AboutController::class,   'index']);
    $r->get('/contact',       [AboutController::class,   'contact']);

    /* ============================================================
       CART (no login required)
       ============================================================ */
    $r->get('/cart',         [CartController::class, 'index']);
    $r->post('/cart/add',    [CartController::class, 'add'],    [CsrfMiddleware::class]);
    $r->post('/cart/update', [CartController::class, 'update'], [CsrfMiddleware::class]);
    $r->post('/cart/remove', [CartController::class, 'remove'], [CsrfMiddleware::class]);

    /* ============================================================
       GUEST ONLY (login / register / password reset)
       ============================================================ */
    $r->get('/login',  [LoginController::class, 'show'], [GuestMiddleware::class]);
    $r->post('/login', [LoginController::class, 'store'], [
        GuestMiddleware::class,
        CsrfMiddleware::class,
        RateLimitMiddleware::class . ':login,5,300',
    ]);

    $r->get('/register',  [RegisterController::class, 'show'], [GuestMiddleware::class]);
    $r->post('/register', [RegisterController::class, 'store'], [
        GuestMiddleware::class,
        CsrfMiddleware::class,
        RateLimitMiddleware::class . ':register,10,600',
    ]);

    $r->get('/forgot-password',  [ForgotPasswordController::class, 'show'], [GuestMiddleware::class]);
    $r->post('/forgot-password', [ForgotPasswordController::class, 'send'], [
        GuestMiddleware::class,
        CsrfMiddleware::class,
        RateLimitMiddleware::class . ':forgot,3,900',
    ]);

    $r->get('/reset-password/{token}',  [ResetPasswordController::class, 'show'], [GuestMiddleware::class]);
    $r->post('/reset-password/{token}', [ResetPasswordController::class, 'update'], [
        GuestMiddleware::class,
        CsrfMiddleware::class,
        RateLimitMiddleware::class . ':reset,5,900',
    ]);

    /* ============================================================
       AUTHENTICATED (any logged-in user)
       ============================================================ */
    $r->post('/logout', [LogoutController::class, 'destroy'], [AuthMiddleware::class, CsrfMiddleware::class]);

    $r->get('/checkout',  [CheckoutController::class, 'show'], [AuthMiddleware::class]);
    $r->post('/checkout', [CheckoutController::class, 'store'], [
        AuthMiddleware::class,
        CsrfMiddleware::class,
        RateLimitMiddleware::class . ':checkout,30,600',
    ]);

    $r->get('/orders',       [OrderController::class, 'index'], [AuthMiddleware::class]);
    $r->get('/orders/{id}',  [OrderController::class, 'show'],  [AuthMiddleware::class]);

    /* ============================================================
       PAYMENTS
       ============================================================ */
    $r->get('/orders/{id}/pay',  [PaymentController::class, 'show'],    [AuthMiddleware::class]);
    $r->post('/orders/{id}/pay', [PaymentController::class, 'process'], [AuthMiddleware::class, CsrfMiddleware::class]);

    /* ============================================================
       PROFILE
       ============================================================ */
    $r->get('/profile',                [ProfileController::class, 'index'],          [AuthMiddleware::class]);
    $r->get('/profile/orders',         [ProfileController::class, 'orders'],         [AuthMiddleware::class]);
    $r->get('/profile/settings',       [ProfileController::class, 'settings'],       [AuthMiddleware::class]);
    $r->post('/profile/settings',      [ProfileController::class, 'updateSettings'], [AuthMiddleware::class, CsrfMiddleware::class]);
    $r->post('/profile/password',      [ProfileController::class, 'updatePassword'], [AuthMiddleware::class, CsrfMiddleware::class, RateLimitMiddleware::class . ':profile-pwd,5,900']);
    $r->post('/profile/avatar',        [ProfileController::class, 'uploadAvatar'],   [AuthMiddleware::class, CsrfMiddleware::class, RateLimitMiddleware::class . ':avatar,10,3600']);
    $r->post('/profile/avatar/remove', [ProfileController::class, 'removeAvatar'],   [AuthMiddleware::class, CsrfMiddleware::class]);

    /* ============================================================
       WEBHOOKS (signature verified by gateway)
       ============================================================ */
    $r->post('/webhooks/{provider}', [PaymentWebhookController::class, 'handle']);

    /* ============================================================
       ADMIN PANEL (admin | manager | staff)
       ============================================================ */
    $admin = [RoleMiddleware::class . ':admin,manager,staff'];

    $r->get('/admin', [DashboardController::class, 'index'], $admin);

    // Products
    $r->get('/admin/products',               [AdminProductController::class, 'index'],   $admin);
    $r->get('/admin/products/create',        [AdminProductController::class, 'create'],  $admin);
    $r->post('/admin/products',              [AdminProductController::class, 'store'],   array_merge($admin, [CsrfMiddleware::class]));
    $r->get('/admin/products/{id}/edit',     [AdminProductController::class, 'edit'],    $admin);
    $r->post('/admin/products/{id}',         [AdminProductController::class, 'update'],  array_merge($admin, [CsrfMiddleware::class]));
    $r->post('/admin/products/{id}/delete',  [AdminProductController::class, 'destroy'], array_merge($admin, [CsrfMiddleware::class]));
    $r->post('/admin/products/{id}/restore', [AdminProductController::class, 'restore'], array_merge($admin, [CsrfMiddleware::class]));

    // Orders
    $r->get('/admin/orders',              [AdminOrderController::class, 'index'],        $admin);
    $r->get('/admin/orders/{id}',         [AdminOrderController::class, 'show'],         $admin);
    $r->post('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus'], array_merge($admin, [CsrfMiddleware::class]));

    // Users
    $r->get('/admin/users',              [AdminUserController::class, 'index'],         $admin);
    $r->post('/admin/users/{id}/role',   [AdminUserController::class, 'updateRole'],    array_merge($admin, [CsrfMiddleware::class]));
    $r->post('/admin/users/{id}/toggle', [AdminUserController::class, 'toggleActive'],  array_merge($admin, [CsrfMiddleware::class]));

    // Reports
    $r->get('/admin/reports', [AdminReportController::class, 'index'], $admin);
};