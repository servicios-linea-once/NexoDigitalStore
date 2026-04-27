<?php
// Orders
Route::get('/orders',        \App\Http\Controllers\Orders\IndexController::class)->name('orders.index');
Route::get('/orders/{ulid}', \App\Http\Controllers\Orders\ShowController::class)->name('orders.show');
