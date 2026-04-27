<?php
// Profile
Route::get   ('/profile',          \App\Http\Controllers\Profile\IndexController::class)->name('profile.index');
Route::put   ('/profile',          \App\Http\Controllers\Profile\UpdateController::class)->name('profile.update');
Route::delete('/profile',          \App\Http\Controllers\Profile\DestroyController::class)->name('profile.destroy');

// Profile - Cambio de contraseña (usuario autenticado, distinto del reset por email)
Route::put   ('/profile/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])->name('password.change');

// Profile - Sesiones activas
Route::delete('/profile/sessions', [\App\Http\Controllers\Profile\SessionsController::class, 'destroyOthers'])->name('sessions.destroy');

// Profile - 2FA
Route::post  ('/profile/two-factor/enable',  [\App\Http\Controllers\Profile\TwoFactorController::class, 'enable'])->name('two-factor.enable');
Route::post  ('/profile/two-factor/confirm', [\App\Http\Controllers\Profile\TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
Route::delete('/profile/two-factor',         [\App\Http\Controllers\Profile\TwoFactorController::class, 'disable'])->name('two-factor.disable');

// Profile - Telegram vinculación
Route::post  ('/profile/telegram/token', \App\Http\Controllers\Profile\Telegram\GenerateTokenController::class)->name('profile.telegram.token');
Route::delete('/profile/telegram',       \App\Http\Controllers\Profile\Telegram\UnlinkController::class)->name('profile.telegram.unlink');
