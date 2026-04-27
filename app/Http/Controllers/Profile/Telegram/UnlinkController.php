<?php

namespace App\Http\Controllers\Profile\Telegram;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TelegramUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnlinkController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'telegram_link_token' => null,
            'telegram_link_token_expires_at' => null,
        ])->save();

        TelegramUser::where('user_id', $user->id)->update([
            'user_id' => null,
            'is_linked' => false,
            'link_token' => null,
            'link_token_expires_at' => null,
        ]);

        AuditLog::record('telegram_unlinked', $user->id);

        return back()->with('success', 'Telegram desvinculado correctamente.');
    }
}
