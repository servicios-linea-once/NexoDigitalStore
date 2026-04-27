<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class HelpCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser): JsonResponse
    {
        $this->context->telegram->sendMessage(
            $chatId,
            "📖 *Comandos disponibles — Nexo Digital Store*\n\n"
            ."🛍️ *Catálogo*\n"
            ."/catalogo — Ver productos destacados\n"
            ."/buscar [texto] — Buscar producto\n\n"
            ."📦 *Mis pedidos*\n"
            ."/pedidos — Ver últimos pedidos\n"
            ."/carrito — Ver tu carrito actual\n\n"
            ."💰 *Cuenta*\n"
            ."/saldo — Ver saldo NexoTokens (NT)\n"
            ."/vincular — Vincular cuenta de la web\n"
            ."/desvincular — Desconectar cuenta\n\n"
            .'❓ /ayuda — Esta ayuda'
        );

        return $this->context->ok();
    }
}
