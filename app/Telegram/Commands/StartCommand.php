<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Callbacks\ConsumeLinkTokenAction;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class StartCommand
{
    public function __construct(
        protected TelegramBotContext $context,
        protected ConsumeLinkTokenAction $consumeLinkToken,
    ) {
    }

    public function __invoke(int $chatId, string $args, TelegramUser $tgUser): JsonResponse
    {
        if (str_starts_with($args, 'vincular_')) {
            return ($this->consumeLinkToken)($chatId, substr($args, 9), $tgUser);
        }

        $name = $tgUser->first_name ?: 'usuario';
        $linked = $tgUser->is_linked ? '✅ Cuenta vinculada' : '❌ Sin cuenta vinculada';

        $this->context->telegram->sendMessage(
            $chatId,
            "👋 *¡Bienvenido a Nexo Digital Store!*\n\n"
            ."Hola *{$name}*, soy tu asistente de compras digital.\n\n"
            ."*Estado:* {$linked}\n\n"
            ."🎮 Compra claves de juegos, gift cards y suscripciones directamente desde Telegram.\n\n"
            .'Usa /ayuda para ver todos los comandos.',
            TelegramService::inlineKeyboard([
                [['text' => '🛍️ Ver catálogo', 'callback_data' => 'cmd_catalogo']],
                [['text' => '🔗 Vincular mi cuenta', 'callback_data' => 'cmd_vincular']],
                [['text' => '❓ Ayuda', 'callback_data' => 'cmd_ayuda']],
            ])
        );

        return $this->context->ok();
    }
}
