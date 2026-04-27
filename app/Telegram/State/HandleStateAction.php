<?php

namespace App\Telegram\State;

use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class HandleStateAction
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, string $text, TelegramUser $tgUser): JsonResponse
    {
        $tgUser->update(['state' => 'idle']);
        $this->context->telegram->sendMessage($chatId, 'Operación cancelada. Usa /ayuda para ver los comandos.');

        return $this->context->ok();
    }
}
