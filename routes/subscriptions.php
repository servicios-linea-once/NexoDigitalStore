<?php
// Subscriptions
Route::get   ('/subscriptions',                 \App\Http\Controllers\Subscriptions\IndexController::class)->name('subscriptions.index');
Route::post  ('/subscriptions/purchase',        \App\Http\Controllers\Subscriptions\PurchaseController::class)->name('subscriptions.purchase');
Route::put   ('/subscriptions/{ulid}/renew',    \App\Http\Controllers\Subscriptions\RenewController::class)->name('subscriptions.renew');
Route::delete('/subscriptions/{ulid}/cancel',   \App\Http\Controllers\Subscriptions\CancelController::class)->name('subscriptions.cancel');
