<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get   ('/users',                      App\Http\Controllers\Admin\Users\IndexController::class)->name('users.index');
    Route::get   ('/users/{id}',                 App\Http\Controllers\Admin\Users\ShowController::class)->name('users.show');
    Route::put   ('/users/{id}',                 App\Http\Controllers\Admin\Users\UpdateController::class)->name('users.update');
    Route::delete('/users/{id}',                 App\Http\Controllers\Admin\Users\DestroyController::class)->name('users.destroy');
    Route::patch ('/users/{id}/toggle-status',   App\Http\Controllers\Admin\Users\ToggleStatusController::class)->name('users.toggle-status');
    Route::patch ('/users/{id}/role',            App\Http\Controllers\Admin\Users\UpdateRoleController::class)->name('users.role');

    // Roles & Permissions (Spatie)
    Route::get   ('/roles',             [App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles.index');
    Route::post  ('/roles',             [App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
    Route::put   ('/roles/{id}',        [App\Http\Controllers\Admin\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{id}',        [App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post  ('/roles/assign',      [App\Http\Controllers\Admin\RoleController::class, 'assign'])->name('roles.assign');
    Route::post  ('/roles/bulk-assign', [App\Http\Controllers\Admin\RoleController::class, 'bulkAssign'])->name('roles.bulk-assign');

    // Categories
    Route::get   ('/categories',         App\Http\Controllers\Admin\Categories\IndexController::class)->name('categories.index');
    Route::post  ('/categories',         App\Http\Controllers\Admin\Categories\StoreController::class)->name('categories.store');
    Route::put   ('/categories/{id}',    App\Http\Controllers\Admin\Categories\UpdateController::class)->name('categories.update');
    Route::delete('/categories/{id}',   App\Http\Controllers\Admin\Categories\DestroyController::class)->name('categories.destroy');

    // Orders — eliminadas (unificadas en admin.store.orders.*)

    // Subscriptions
    Route::get   ('/subscriptions',              [App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post  ('/subscriptions/assign',       [App\Http\Controllers\Admin\SubscriptionController::class, 'assign'])->name('subscriptions.assign');
    Route::delete('/subscriptions/{id}/revoke',  [App\Http\Controllers\Admin\SubscriptionController::class, 'revoke'])->name('subscriptions.revoke');
    Route::put   ('/subscriptions/plans/{id}',   [App\Http\Controllers\Admin\SubscriptionController::class, 'updatePlan'])->name('subscriptions.plans.update');

    // Reviews & Subscription Requests
    Route::get ('/reviews',              \App\Http\Controllers\Admin\Reviews\IndexController::class)->name('reviews.index');
    Route::post('/reviews/{id}/approve', \App\Http\Controllers\Admin\Reviews\ApproveController::class)->name('reviews.approve');
    Route::post('/reviews/{id}/reject',  \App\Http\Controllers\Admin\Reviews\RejectController::class)->name('reviews.reject');
    Route::post('/reviews/{id}/flag',    \App\Http\Controllers\Admin\Reviews\FlagController::class)->name('reviews.flag');
    Route::post('/reviews/{id}/reply',   \App\Http\Controllers\Admin\Reviews\ReplyController::class)->name('reviews.reply');

    Route::get ('/subscription-requests',                               \App\Http\Controllers\Admin\SubscriptionRequests\IndexController::class)->name('subscription-requests.index');
    Route::post('/subscription-requests/{subscriptionRequest}/approve', \App\Http\Controllers\Admin\SubscriptionRequests\ApproveController::class)->name('subscription-requests.approve');
    Route::post('/subscription-requests/{subscriptionRequest}/reject',  \App\Http\Controllers\Admin\SubscriptionRequests\RejectController::class)->name('subscription-requests.reject');

    // Audit Logs
    Route::get('/audit-logs', \App\Http\Controllers\Admin\AuditLogs\IndexController::class)->name('audit-logs');

    // ── Gestión de Tienda (Store Management — Nexo eStore único vendedor) ──
    Route::prefix('store')->name('store.')->group(function () {

        // Store dashboard unified with admin.dashboard (redirect)
        Route::get('/dashboard', fn() => redirect()->route('admin.dashboard'))->name('dashboard');

        // Products CRUD
        Route::get    ('/products',                App\Http\Controllers\Admin\Store\Products\IndexController::class)->name('products.index');
        Route::get    ('/products/create',         App\Http\Controllers\Admin\Store\Products\CreateController::class)->name('products.create');
        Route::post   ('/products',                App\Http\Controllers\Admin\Store\Products\StoreController::class)->name('products.store');
        Route::get    ('/products/{ulid}/edit',    App\Http\Controllers\Admin\Store\Products\EditController::class)->name('products.edit');
        Route::put    ('/products/{ulid}',         App\Http\Controllers\Admin\Store\Products\UpdateController::class)->name('products.update');
        Route::delete ('/products/{ulid}',         App\Http\Controllers\Admin\Store\Products\DestroyController::class)->name('products.destroy');
        Route::patch  ('/products/{ulid}/toggle-status', App\Http\Controllers\Admin\Store\Products\ToggleStatusController::class)->name('products.toggle-status');

        // Keys / Licenses
        Route::get   ('/keys',          [App\Http\Controllers\Admin\Store\KeyController::class, 'index'])->name('keys.index');
        Route::post  ('/keys/import',   [App\Http\Controllers\Admin\Store\KeyController::class, 'import'])->name('keys.import');
        Route::delete('/keys/{id}',     [App\Http\Controllers\Admin\Store\KeyController::class, 'destroy'])->name('keys.destroy');

        // Store Orders (unified — includes refund action)
        Route::get ('/orders',               App\Http\Controllers\Admin\Store\Orders\IndexController::class)->name('orders.index');
        Route::get ('/orders/{ulid}',        App\Http\Controllers\Admin\Store\Orders\ShowController::class)->name('orders.show');
        Route::post('/orders/{ulid}/refund', App\Http\Controllers\Admin\Store\Orders\RefundController::class)->name('orders.refund');

        // Earnings & Deliveries
        Route::get  ('/earnings',  fn() => redirect()->route('admin.dashboard'))->name('earnings');
        Route::get  ('/deliveries',              [App\Http\Controllers\Admin\DeliveryController::class, 'index'])->name('deliveries.index');
        Route::post ('/deliveries/{ulid}/deliver', [App\Http\Controllers\Admin\Store\DeliveryController::class, 'deliver'])->name('deliveries.deliver');
        Route::post ('/deliveries/{ulid}/retry',   [App\Http\Controllers\Admin\Store\DeliveryController::class, 'retry'])->name('deliveries.retry');

        // Promotions
        Route::resource('promotions', App\Http\Controllers\Admin\Store\PromotionController::class);
    });

    // ── [PUNTO-2] Store Settings (reemplaza SellerProfile) ───────────────────
    Route::get('/settings/store', [App\Http\Controllers\Admin\StoreSettingController::class, 'index'])->name('settings');
    Route::post('/settings/store', [App\Http\Controllers\Admin\StoreSettingController::class, 'update'])->name('settings.update');
    Route::patch('/settings/store/{key}', [App\Http\Controllers\Admin\StoreSettingController::class, 'updateOne'])->name('settings.key');

    // General Site Settings (Spatie Settings)
    Route::get('/settings/general', [App\Http\Controllers\Admin\GeneralSettingsController::class, 'edit'])->name('settings.general');
    Route::put('/settings/general', [App\Http\Controllers\Admin\GeneralSettingsController::class, 'update'])->name('settings.general.update');
});
