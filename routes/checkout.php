<?php
// Checkout
Route::get('/checkout',  \App\Http\Controllers\Checkout\IndexController::class)->name('checkout.index');
Route::post('/checkout', \App\Http\Controllers\Checkout\ProcessController::class)->name('checkout.process')->middleware('throttle:10,1');
