<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionsController extends Controller
{
    /**
     * Historial de inicios de sesión del usuario (lee de audit_logs).
     */
    public static function historyFor(int $userId, int $limit = 20): array
    {
        return AuditLog::where('user_id', $userId)
            ->whereIn('event', ['login_success', 'login_failed', 'logout', 'registered_oauth'])
            ->latest()
            ->limit($limit)
            ->get(['id', 'event', 'ip_address', 'user_agent', 'created_at', 'new_values'])
            ->map(fn (AuditLog $log) => [
                'id'            => $log->id,
                'event'         => $log->event,
                'ip_address'    => $log->ip_address,
                'user_agent'    => $log->user_agent,
                'is_mobile'     => self::isMobile($log->user_agent),
                'provider'      => $log->new_values['provider'] ?? null,
                'last_activity' => $log->created_at->timestamp,
            ])
            ->toArray();
    }

    /**
     * Cierra todas las sesiones distintas a la actual.
     * Requiere que el usuario reingrese su contraseña para mayor seguridad.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->input('password'));

        AuditLog::record('sessions_revoked', $request->user()->id);

        // Borra registros de sesión en DB si se usa el driver "database"
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return back()->with('success', 'Se cerraron las otras sesiones activas.');
    }

    private static function isMobile(?string $userAgent): bool
    {
        if (! $userAgent) {
            return false;
        }

        return (bool) preg_match('/(mobile|android|iphone|ipad|ipod)/i', $userAgent);
    }
}
