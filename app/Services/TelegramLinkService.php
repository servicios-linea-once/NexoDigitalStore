<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TelegramLinkService
{
    public function generateForUser(User $user, ?int $ttlSeconds = null): array
    {
        $ttlSeconds ??= 900;
        $token = Str::random(32);

        $user->forceFill([
            'telegram_link_token' => $token,
            'telegram_link_token_expires_at' => now()->addSeconds($ttlSeconds),
        ])->save();

        $botUsername = config('services.telegram.bot_username', 'NexoDigitalBot');

        return [
            'token' => $token,
            'expires_in' => $ttlSeconds,
            'link' => "https://t.me/{$botUsername}?start=vincular_{$token}",
        ];
    }

    public function consumeToken(TelegramUser $tgUser, string $token): ?User
    {
        return DB::transaction(function () use ($tgUser, $token) {
            $user = User::query()
                ->where('telegram_link_token', $token)
                ->where('telegram_link_token_expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $user) {
                return null;
            }

            TelegramUser::query()
                ->where('user_id', $user->id)
                ->whereKeyNot($tgUser->id)
                ->update([
                    'user_id' => null,
                    'is_linked' => false,
                ]);

            $tgUser->update([
                'user_id' => $user->id,
                'is_linked' => true,
                'link_token' => null,
                'link_token_expires_at' => null,
            ]);

            $user->forceFill([
                'telegram_link_token' => null,
                'telegram_link_token_expires_at' => null,
            ])->save();

            AuditLog::record('telegram_linked', $user->id, $tgUser, [
                'telegram_id' => $tgUser->telegram_id,
                'telegram_username' => $tgUser->username,
            ]);

            return $user->fresh();
        });
    }
}
