<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Cuenta desactivada.'], 403);
        }

        // Check if 2FA is enabled - require OTP verification
        $twoFactor = $user->twoFactorAuth;
        if ($twoFactor?->is_enabled) {
            // Create a temporary token for 2FA verification
            $tempToken = $user->createToken('2fa-pending', ['*'], now()->addMinutes(5));

            return response()->json([
                'requires_2fa' => true,
                'message' => 'Se requiere verificación de dos factores.',
                'temp_token' => $tempToken->plainTextToken,
            ], 403);
        }

        return $this->createSuccessfulLoginResponse($user);
    }

    /**
     * Verify 2FA OTP and complete login
     */
    public function verify2fa(Request $request): JsonResponse
    {
        $request->validate([
            'temp_token' => ['required', 'string'],
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        // Find user by temp token
        $tempToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->temp_token);

        if (! $tempToken || $tempToken->name !== '2fa-pending') {
            return response()->json(['message' => 'Token inválido o expirado.'], 401);
        }

        $user = $tempToken->tokenable;

        if (! $user || ! $this->twoFactorService->verify($user, $request->otp)) {
            // Delete invalid temp token
            $tempToken->delete();
            return response()->json(['message' => 'Código OTP inválido.'], 401);
        }

        // Delete 2FA pending token
        $tempToken->delete();

        return $this->createSuccessfulLoginResponse($user);
    }

    /**
     * Enable 2FA for the authenticated user
     * Returns QR code SVG for setup
     */
    public function enable2fa(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if already enabled
        if ($user->twoFactorAuth?->is_enabled) {
            return response()->json(['message' => '2FA ya está habilitado.'], 400);
        }

        $record = $this->twoFactorService->generateSecret($user);
        $qrSvg = $this->twoFactorService->qrCodeSvg($user, $record->secret);

        // Return base64 encoded QR for easier mobile handling
        $qrBase64 = base64_encode($qrSvg);

        return response()->json([
            'secret' => $record->secret,
            'qr_svg' => $qrSvg,
            'qr_base64' => $qrBase64,
            'message' => 'Escanea el código QR con tu app de autenticación y confirma con un OTP.',
        ]);
    }

    /**
     * Confirm 2FA setup with OTP verification
     */
    public function confirm2fa(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();
        $record = $user->twoFactorAuth;

        if (! $record) {
            return response()->json(['message' => 'Primero genera el código QR.'], 400);
        }

        if ($record->is_enabled) {
            return response()->json(['message' => '2FA ya está confirmado.'], 400);
        }

        if (! $this->twoFactorService->confirm($user, $request->otp)) {
            return response()->json(['message' => 'Código OTP inválido.'], 401);
        }

        // Refresh the record after confirmation
        $record->refresh();

        return response()->json([
            'enabled' => true,
            'recovery_codes' => $record->recovery_codes,
            'message' => '2FA habilitado correctamente. Guarda tus códigos de recuperación.',
        ]);
    }

    /**
     * Disable 2FA (requires current OTP)
     */
    public function disable2fa(Request $request): JsonResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();

        if (! $this->twoFactorService->verify($user, $request->otp)) {
            return response()->json(['message' => 'Código OTP inválido.'], 401);
        }

        $this->twoFactorService->disable($user);

        return response()->json(['message' => '2FA deshabilitado.']);
    }

    /**
     * Get user's 2FA status
     */
    public function twoFactorStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $record = $user->twoFactorAuth;

        return response()->json([
            'enabled' => $record?->is_enabled ?? false,
            'enabled_at' => $record?->enabled_at?->toIso8601String(),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'buyer',
            'is_active' => true,
        ]);

        // Auto-assign Free subscription
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

        $token = $user->createToken('api-mobile', ['*'], now()->addDays(30));

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => (new AuthenticatedUserResource($user))->resolve(),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /**
     * Revoke all tokens (logout from all devices)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Todas las sesiones han sido cerradas.']);
    }

    /**
     * Refresh token - invalidate current and issue new one
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();

        // Delete old token
        $currentToken->delete();

        // Create new token
        $newToken = $user->createToken('api-mobile', ['*'], now()->addDays(30));

        return response()->json([
            'token' => $newToken->plainTextToken,
            'expires_at' => $newToken->accessToken->expires_at,
            'message' => 'Token actualizado.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'Si el email existe, recibirás un enlace de recuperación.']);
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    private function createSuccessfulLoginResponse(User $user): JsonResponse
    {
        $token = $user->createToken('api-mobile', ['*'], now()->addDays(30));

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
            'user' => (new AuthenticatedUserResource($user))->resolve(),
        ]);
    }
}
