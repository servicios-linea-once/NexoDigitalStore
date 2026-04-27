<?php
// Rutas web/API (dependiendo de dónde las tengas declaradas, generalmente en el grupo middleware auth)
Route::get ('/licenses',                            \App\Http\Controllers\Licenses\IndexController::class)->name('licenses.index');
Route::get ('/licenses/{ulid}',                     \App\Http\Controllers\Licenses\ShowController::class)->name('licenses.show');
Route::post('/licenses/{ulid}/activate',            \App\Http\Controllers\Licenses\ActivateController::class)->name('licenses.activate');
Route::post('/licenses/activations/{ulid}/revoke',  \App\Http\Controllers\Licenses\DeactivateController::class)->name('licenses.deactivate');
