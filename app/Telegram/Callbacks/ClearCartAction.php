<?php

namespace App\Telegram\Callbacks;

use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class ClearCartAction
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser): JsonResponse
    {
        $tgUser->update(['cart' => []]);
        $this->context->telegram->sendMessage($chatId, '🗑️ Carrito vaciado.');

        return $this->context->ok();
    }
}
