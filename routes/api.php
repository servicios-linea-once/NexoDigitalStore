<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Resources\AuthenticatedUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1 (Laravel Sanctum)
| Used by: Flutter mobile app, external integrations
|
| Rate Limiting: Applied via middleware in bootstrap/app.php
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // ── Public endpoints (60 requests/minute) ──────────────────────────────
    Route::middleware(['throttle:api-public'])->group(function () {
        Route::get('/search', App\Http\Controllers\Api\V1\GlobalSearchController::class);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{ulid}', [ProductController::class, 'show']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/currencies', [CurrencyController::class, 'index']);
    });

    // ── Authentication (5 requests/minute per IP) ───────────────────────────
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-auth');
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api-auth');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:api-auth');
        Route::post('/2fa/verify', [AuthController::class, 'verify2fa'])->middleware('throttle:api-auth');
    });

    // ── Authenticated endpoints (Sanctum) ───────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:api-authenticated'])->group(function () {

        Route::get('/user', fn (Request $r) => response()->json(
            (new AuthenticatedUserResource($r->user()->load('wallet')))->resolve()
        ));

        // Auth management
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);

        // 2FA Management
        Route::get    ('/auth/2fa/status',  [AuthController::class, 'twoFactorStatus']);
        Route::post   ('/auth/2fa/enable',  [AuthController::class, 'enable2fa']);
        Route::post   ('/auth/2fa/confirm', [AuthController::class, 'confirm2fa']);
        Route::post   ('/auth/2fa/disable', [AuthController::class, 'disable2fa']);

        // Cart & Orders
        Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
        Route::post('/orders/{ulid}/pay', [OrderController::class, 'pay']);
        Route::get('/orders/{ulid}/payment-status', [OrderController::class, 'paymentStatus']);

        // License management (key feature for Flutter)
        Route::prefix('licenses')->name('licenses.')->group(function () {
            Route::get('/', [LicenseController::class, 'index']);
            Route::get('/{ulid}', [LicenseController::class, 'show']);
            Route::post('/{ulid}/activate', [LicenseController::class, 'activate']);
            Route::post('/{ulid}/deactivate', [LicenseController::class, 'deactivate']);
            Route::post('/{ulid}/heartbeat', [LicenseController::class, 'heartbeat']);
        });

        // NexoTokens wallet
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [WalletController::class, 'show']);
            Route::get('/transactions', [WalletController::class, 'transactions']);
        });

        // Subscriptions
        Route::get('/subscriptions/plans', [SubscriptionController::class, 'plans']);
        Route::get('/subscriptions/current', [SubscriptionController::class, 'current']);

        // Profile
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/profile/password', [ProfileController::class, 'changePassword']);
        Route::get('/profile/sessions', [ProfileController::class, 'sessions']);
        Route::get('/profile/security', [ProfileController::class, 'security']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

        // Wishlist
        Route::get    ('/wishlist',         [App\Http\Controllers\Api\V1\WishlistController::class, 'index']);
        Route::post   ('/wishlist/toggle',  [App\Http\Controllers\Api\V1\WishlistController::class, 'toggle']);
        Route::delete ('/wishlist',         [App\Http\Controllers\Api\V1\WishlistController::class, 'clear']);

        // ── Admin API Endpoints ───────────────────────────────────────────
        Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
            // User Management
            Route::get('/users', [App\Http\Controllers\Api\V1\Admin\UserController::class, 'index']);
            Route::get('/users/{id}', [App\Http\Controllers\Api\V1\Admin\UserController::class, 'show']);
            Route::patch('/users/{id}/status', [App\Http\Controllers\Api\V1\Admin\UserController::class, 'toggleStatus']);

            // Product Management
            Route::apiResource('products', App\Http\Controllers\Api\V1\Admin\ProductController::class);

            // Store Settings (Spatie)
            // Route::get('/settings/general', [App\Http\Controllers\Api\V1\Admin\SettingsController::class, 'show']);
            // Route::put('/settings/general', [App\Http\Controllers\Api\V1\Admin\SettingsController::class, 'update']);
        });
    });
});
