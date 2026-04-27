<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\AuditLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function forgotForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Send a password reset link to the given user.
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {

        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            return back()->with('status', 'Se envió el enlace de recuperación si el email existe.');
        }

        return back()->withErrors(['email' => 'No pudimos enviar el enlace. Verifica el email.']);
    }

    /**
     * Show the reset password form.
     */
    public function resetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the given user's password.
     */
    public function reset(ResetPasswordRequest $request): RedirectResponse
    {

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])
                    ->setRememberToken(Str::random(60));
                $user->save();

                AuditLog::record('password_reset', $user->id);
                event(new PasswordReset($user));
            }
        );

        if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', '¡Contraseña restablecida! Inicia sesión.');
        }

        return back()->withErrors(['email' => 'El enlace no es válido o ha expirado.']);
    }

    /**
     * Update the current user's password.
     */
    public function update(UpdatePasswordRequest $request): RedirectResponse
    {

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLog::record('password_changed', $request->user()->id);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
