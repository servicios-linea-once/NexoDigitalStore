<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class UnlinkCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser): JsonResponse
    {
        if (! $tgUser->is_linked) {
            $this->context->telegram->sendMessage($chatId, 'No tienes ninguna cuenta vinculada.');

            return $this->context->ok();
        }

        $tgUser->update(['user_id' => null, 'is_linked' => false]);

        $this->context->telegram->sendMessage(
            $chatId,
            "🔓 Cuenta desvinculada correctamente.\n\nPuedes volver a vincularla cuando quieras con /vincular."
        );

        return $this->context->ok();
    }
}
