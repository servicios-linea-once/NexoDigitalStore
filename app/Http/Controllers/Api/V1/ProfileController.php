<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TwoFactorAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'avatar' => ['sometimes', 'url', 'max:500'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado.',
            'user' => $request->user()->fresh(['wallet']),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (! Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'La contraseña actual es incorrecta.'], 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    /**
     * GET /profile/sessions - Historial de sesiones del usuario
     */
    public function sessions(Request $request): JsonResponse
    {
        $sessions = AuditLog::where('user_id', $request->user()->id)
            ->whereIn('event', ['login_success', 'login_failed', 'logout', 'registered_oauth'])
            ->latest()
            ->limit(20)
            ->get(['id', 'event', 'ip_address', 'user_agent', 'created_at', 'new_values'])
            ->map(fn (AuditLog $log) => [
                'id'            => $log->id,
                'event'         => $log->event,
                'ip_address'    => $log->ip_address,
                'user_agent'    => $log->user_agent,
                'is_mobile'     => (bool) preg_match('/(mobile|android|iphone|ipad|ipod)/i', $log->user_agent ?? ''),
                'provider'      => $log->new_values['provider'] ?? null,
                'last_activity' => $log->created_at->timestamp,
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * GET /profile/security - Información de seguridad del perfil
     */
    public function security(Request $request): JsonResponse
    {
        $user = $request->user()->load('telegramUser');

        $twoFactor = TwoFactorAuth::where('user_id', $user->id)->where('is_enabled', true)->first();

        $recentActivity = AuditLog::where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get(['id', 'event', 'ip_address', 'created_at', 'new_values'])
            ->map(fn (AuditLog $log) => [
                'id'         => $log->id,
                'event'      => $log->event,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return response()->json([
            'has_two_factor'   => $twoFactor ? true : false,
            'two_factor_codes_remaining' => $twoFactor?->recovery_codes_remaining ?? 0,
            'linked_google'   => ! empty($user->google_id),
            'linked_steam'    => ! empty($user->steam_id),
            'linked_telegram' => $user->telegramUser?->is_linked ?? false,
            'recent_activity' => $recentActivity,
        ]);
    }
}
