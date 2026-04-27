<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class CartCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser): JsonResponse
    {
        $cart = $tgUser->cart ?? [];

        if (empty($cart)) {
            $this->context->telegram->sendMessage(
                $chatId,
                "🛒 Tu carrito está vacío.\n\nUsa /catalogo para explorar productos o escribe lo que buscas."
            );

            return $this->context->ok();
        }

        $text = "🛒 *Tu carrito:*\n\n";
        $totalUsd = 0;

        foreach ($cart as $item) {
            $text .= "• {$item['name']} — \${$item['price']}\n";
            $totalUsd += $item['price'];
        }

        $ntRate = config('nexo.token.rate_to_usd', 0.10);
        $totalNt = round($totalUsd / $ntRate);
        $text .= "\n*Total: \$".number_format($totalUsd, 2)." USD*\n*Costo en NT: {$totalNt} NT*";

        $this->context->telegram->sendMessage(
            $chatId,
            $text,
            TelegramService::inlineKeyboard([
                [['text' => '💳 Pagar con NT', 'callback_data' => 'checkout_nt']],
                [['text' => '🗑️ Vaciar carrito', 'callback_data' => 'cart_clear']],
            ])
        );

        return $this->context->ok();
    }
}
