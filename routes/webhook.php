<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\MercadoPagoWebhookController;
use App\Http\Controllers\Webhook\PayPalWebhookController;
use App\Http\Controllers\Webhook\TelegramWebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::prefix('webhook')->name('webhook.')->withoutMiddleware([VerifyCsrfToken::class])->group(function () {
    Route::post('/paypal', [PayPalWebhookController::class, 'handle'])->name('paypal');
    Route::post('/mercadopago', [MercadoPagoWebhookController::class, 'handle'])->name('mercadopago');
    Route::post('/telegram', [TelegramWebhookController::class, 'handle'])->name('telegram');
});
