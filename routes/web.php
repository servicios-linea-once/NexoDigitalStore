<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Payment\MercadoPagoController;
use App\Http\Controllers\Payment\PayPalController;
use App\Http\Controllers\Products\ByCategoryController;
use App\Http\Controllers\Products\IndexController;
use App\Http\Controllers\Products\ShowController;
use App\Http\Controllers\Reviews\DestroyController;
use App\Http\Controllers\Reviews\StoreController;
use App\Http\Controllers\Reviews\VoteController;
use App\Http\Controllers\UiPreferencesController;
use App\Http\Controllers\WalletTopUp\ProcessController;
use App\Http\Controllers\Wishlist\CheckController;
use App\Http\Controllers\Wishlist\ClearController;
use App\Http\Controllers\Wishlist\ToggleController;
use Illuminate\Support\Facades\Route;

// ── Rutas Públicas ─────────────────────────────────────────────────────────
Route::middleware([])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/terms', fn () => inertia('Legal/Terms'))->name('terms');
    Route::get('/privacy', fn () => inertia('Legal/Privacy'))->name('privacy');
    Route::get('/cookies', fn () => inertia('Legal/Cookies'))->name('cookies');

    // Products (Single-Action Controllers)
    Route::get('/products', IndexController::class)->name('products.index');
    Route::get('/products/{slug}', ShowController::class)->name('products.show');
    Route::get('/category/{slug}', ByCategoryController::class)->name('products.category');
});

// ── Rutas de Compradores ───────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    Route::patch('/ui/preferences', [UiPreferencesController::class, 'update'])->name('ui.preferences');

    // Checkout
    require __DIR__.'/checkout.php';

    // PayPal y MercadoPago
    Route::post('/payment/paypal/create-order', [PayPalController::class, 'createOrder'])->name('payment.paypal.create')->middleware('throttle:5,1');
    Route::post('/payment/paypal/capture-order', [PayPalController::class, 'captureOrder'])->name('payment.paypal.capture')->middleware('throttle:5,1');
    Route::post('/payment/mp/preference', [MercadoPagoController::class, 'createPreference'])->name('payment.mp.preference')->middleware('throttle:5,1');
    Route::get('/payment/mp/success', [MercadoPagoController::class, 'success'])->name('payment.mp.success');
    Route::get('/payment/mp/failure', [MercadoPagoController::class, 'failure'])->name('payment.mp.failure');
    Route::get('/payment/mp/pending', [MercadoPagoController::class, 'pending'])->name('payment.mp.pending');

    // Órdenes
    require __DIR__.'/orders.php';

    // Licencias
    require __DIR__.'/licenses.php';

    // Suscripciones
    require __DIR__.'/subscriptions.php';

    // Perfil
    require __DIR__.'/profile.php';

    // Recarga de Billetera
    Route::get('/wallet/topup', App\Http\Controllers\WalletTopUp\IndexController::class)->name('wallet.topup');
    Route::post('/wallet/topup', ProcessController::class)->name('wallet.topup.process');

    // Reseñas
    Route::post('/reviews', StoreController::class)->name('reviews.store');
    Route::delete('/reviews/{id}', DestroyController::class)->name('reviews.destroy');
    Route::post('/reviews/{id}/vote', VoteController::class)->name('reviews.vote');

    // Solicitudes de Suscripción
    Route::get('/subscription-requests', App\Http\Controllers\SubscriptionRequests\IndexController::class)->name('subscription-requests.index');
    Route::post('/subscription-requests', App\Http\Controllers\SubscriptionRequests\StoreController::class)->name('subscription-requests.store');

    // Lista de Deseos (Wishlist)
    Route::get('/wishlist', App\Http\Controllers\Wishlist\IndexController::class)->name('wishlist.index');
    Route::post('/wishlist/toggle', ToggleController::class)->name('wishlist.toggle');
    Route::get('/wishlist/check', CheckController::class)->name('wishlist.check');
    Route::delete('/wishlist/{id}', App\Http\Controllers\Wishlist\DestroyController::class)->name('wishlist.destroy');
    Route::delete('/wishlist', ClearController::class)->name('wishlist.clear');
});

// ── Importación de Rutas Independientes ────────────────────────────────────
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/webhook.php';
require __DIR__.'/Cart.php';
