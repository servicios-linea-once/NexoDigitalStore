<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class LinkCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, string $args, TelegramUser $tgUser): JsonResponse
    {
        if ($tgUser->is_linked) {
            $this->context->telegram->sendMessage(
                $chatId,
                "✅ Tu cuenta ya está vinculada.\n\nUsa /desvincular si deseas desconectarla."
            );

            return $this->context->ok();
        }

        $this->context->telegram->sendMessage(
            $chatId,
            "🔗 *Vincular cuenta Nexo*\n\n"
            ."Para vincular tu cuenta:\n\n"
            ."1️⃣ Inicia sesión en *".$this->context->storeUrl()."*\n"
            ."2️⃣ Ve a *Perfil → Seguridad*\n"
            ."3️⃣ Haz clic en *\"Vincular Telegram\"*\n"
            ."4️⃣ Escanea el código QR o copia el enlace\n\n"
            ."Enlace directo: ".$this->context->profileSecurityUrl()."\n\n"
            .'Recibirás confirmación aquí mismo cuando se vincule ✅'
        );

        return $this->context->ok();
    }
}
