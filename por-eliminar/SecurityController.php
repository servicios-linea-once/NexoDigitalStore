<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TwoFactorAuth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load('telegramUser');

        $auditLogs = AuditLog::where('user_id', $user->id)
            ->select(['id', 'event', 'new_values', 'ip_address', 'created_at', 'auditable_type', 'auditable_id'])
            ->latest()
            ->limit(20)
            ->get();

        $linkedTelegram = null;
        if ($user->telegramUser && $user->telegramUser->is_linked) {
            $linkedTelegram = $user->telegramUser->username;
        }

        $hasTwoFa = TwoFactorAuth::where('user_id', $user->id)->where('is_enabled', true)->exists();

        return Inertia::render('Profile/Security', [
            'user'           => $user,
            'auditLogs'      => $auditLogs,
            'sessions'       => SessionsController::historyFor($user->id),
            'hasTwoFa'       => $hasTwoFa,
            'linkedGoogle'   => ! empty($user->google_id),
            'linkedSteam'    => ! empty($user->steam_id),
            'linkedTelegram' => $linkedTelegram,
            'botUsername'    => config('services.telegram.bot_username', 'NexoDigitalBot'),
        ]);
    }
}
