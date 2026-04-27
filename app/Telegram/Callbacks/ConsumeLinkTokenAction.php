<?php

namespace App\Telegram\Callbacks;

use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class ConsumeLinkTokenAction
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, string $token, TelegramUser $tgUser): JsonResponse
    {
        $user = $this->context->links->consumeToken($tgUser, $token);

        if (! $user) {
            $this->context->telegram->sendMessage(
                $chatId,
                "❌ El enlace de vinculación es inválido o ha expirado.\n\n"
                .'Genera uno nuevo desde *Perfil → Seguridad* en la web.'
            );

            return $this->context->ok();
        }

        $this->context->telegram->sendMessage(
            $chatId,
            "🎉 *¡Cuenta vinculada exitosamente!*\n\n"
            ."Hola *{$user->name}*, ya puedes:\n\n"
            ."🛍️ Ver tus pedidos con /pedidos\n"
            ."💰 Ver tu saldo NT con /saldo\n"
            .'🎮 Explorar el catálogo con /catalogo'
        );

        return $this->context->ok();
    }
}
