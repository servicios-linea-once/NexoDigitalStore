<?php

namespace App\Telegram\Support;

use App\Services\TelegramLinkService;
use App\Services\TelegramService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class TelegramBotContext
{
    public function __construct(
        public TelegramService $telegram,
        public WalletService $wallets,
        public TelegramLinkService $links,
    ) {
    }

    public function ok(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    public function requireLinked(int $chatId): JsonResponse
    {
        $this->telegram->sendMessage(
            $chatId,
            "🔐 *Necesitas vincular tu cuenta*\n\n"
            ."Ve a *".$this->profileSecurityUrl()."* y haz clic en *\"Vincular Telegram\"*,\n"
            .'o usa el comando /vincular para ver las instrucciones.',
            TelegramService::inlineKeyboard([
                [['text' => '🔗 Cómo vincular', 'callback_data' => 'cmd_vincular']],
            ])
        );

        return $this->ok();
    }

    public function statusEmoji(string $status): string
    {
        return match ($status) {
            'completed' => '✅',
            'pending' => '⏳',
            'failed' => '❌',
            'refunded' => '↩️',
            default => '📋',
        };
    }

    public function storeUrl(): string
    {
        return preg_replace('#^https?://#', '', config('app.url', 'nexodigital.com'));
    }

    public function profileSecurityUrl(): string
    {
        return route('profile.index', ['tab' => 'seguridad']);
    }
}
