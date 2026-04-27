<?php

// ── Cart (session-based) ───────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/cart',             \App\Http\Controllers\Cart\IndexController::class)->name('cart.index');
    Route::post('/cart/add',        \App\Http\Controllers\Cart\AddController::class)->name('cart.add');
    Route::delete('/cart/{ulid}',   \App\Http\Controllers\Cart\RemoveController::class)->name('cart.remove');
    Route::delete('/cart',          \App\Http\Controllers\Cart\ClearController::class)->name('cart.clear');
    Route::get('/cart/count',       \App\Http\Controllers\Cart\CountController::class)->name('cart.count');

    Route::post('/ui-preferences', [\App\Http\Controllers\UiPreferencesController::class, 'update'])->name('ui.preferences');
});
