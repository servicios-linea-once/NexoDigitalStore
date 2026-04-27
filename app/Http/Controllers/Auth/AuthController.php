<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Jobs\SendWelcomeNotification;
use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\Wallet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // ── Login Form ────────────────────────────────────────────────────────
    public function loginForm(): Response
    {
        return Inertia::render('Auth/Login');
    }

    // ── Login ─────────────────────────────────────────────────────────────
    public function login(LoginRequest $request): RedirectResponse
    {

        // Rate limiting: 5 attempts per minute per IP+email
        $key = 'login:'.$request->ip().'|'.$request->email;

        if (RateLimiter::tooManyAttempts($key, config('nexo.security.max_login_attempts', 5))) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => "Demasiados intentos. Intenta de nuevo en {$seconds} segundos.",
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($key, config('nexo.security.lockout_minutes', 15) * 60);

            AuditLog::record('login_failed', null, null, [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
        }

        RateLimiter::clear($key);

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Tu cuenta ha sido desactivada.']);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        AuditLog::record('login_success', $user->id);

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    // ── Register Form ─────────────────────────────────────────────────────
    public function registerForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    // ── Register ──────────────────────────────────────────────────────────
    public function register(RegisterRequest $request): RedirectResponse
    {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'buyer',
            'is_active' => true,
        ]);

        // Auto-create NexoTokens wallet
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => 'NT',
        ]);

        // Auto-assign Free plan
        $freePlan = SubscriptionPlan::where('slug', 'free')->first();
        if ($freePlan) {
            UserSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $freePlan->id,
                'status' => 'active',
                'payment_gateway' => 'manual',
                'payment_reference' => 'auto-free',
                'amount_paid' => 0,
                'currency' => 'USD',
                'starts_at' => now(),
                'expires_at' => null,
                'auto_renew' => false,
            ]);
        }

        event(new Registered($user));              // triggers email verification
        $user->assignRole('buyer');                 // assign Spatie role
        AuditLog::record('registered', $user->id);
        SendWelcomeNotification::dispatch($user->id); // queued — non-blocking


        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')->with('success', '¡Cuenta creada! Verifica tu email para continuar. 🎉');
    }

    // ── Logout ────────────────────────────────────────────────────────────
    public function logout(Request $request): RedirectResponse
    {
        AuditLog::record('logout', Auth::id());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ── OAuth Redirect ────────────────────────────────────────────────────
    public function redirectToProvider(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google', 'steam']), 404);

        return Socialite::driver($provider)->redirect();
    }

    // ── OAuth Callback ────────────────────────────────────────────────────
    public function handleProviderCallback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google', 'steam']), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Error al autenticar con '.ucfirst($provider).'.']);
        }

        $providerIdField = match ($provider) {
            'google' => 'google_id',
            'steam' => 'steam_id',
            default => 'provider_id',
        };

        $user = User::firstOrCreate(
            ['email' => $socialUser->getEmail() ?? $socialUser->getId().'@'.$provider.'.local'],
            [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Usuario',
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                $providerIdField => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'role' => 'buyer',
                'is_active' => true,
                'password' => Str::random(32), // unusable password
                'email_verified_at' => now(),
            ]
        );

        // Update OAuth info if already exists
        if ($user->wasRecentlyCreated) {
            Wallet::create(['user_id' => $user->id, 'balance' => 0, 'currency' => 'NT']);
            // Auto-assign Free plan to OAuth users too
            $freePlan = SubscriptionPlan::where('slug', 'free')->first();
            if ($freePlan) {
                UserSubscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                    'payment_gateway' => 'manual',
                    'payment_reference' => 'auto-free',
                    'amount_paid' => 0,
                    'currency' => 'USD',
                    'starts_at' => now(),
                    'expires_at' => null,
                    'auto_renew' => false,
                ]);
            }
            AuditLog::record('registered_oauth', $user->id, null, ['provider' => $provider]);
            $user->assignRole('buyer'); // assign Spatie role for new OAuth users
        } else {
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                $providerIdField => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar() ?? $user->avatar,
            ]);
            AuditLog::record('login_oauth', $user->id, null, ['provider' => $provider]);
        }

        $user->update(['last_login_at' => now()]);

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->route('home')->with('success', 'Sesión iniciada correctamente.');
    }
}
