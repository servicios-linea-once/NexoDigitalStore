<?php

namespace App\Telegram\Callbacks;

use App\Models\Product;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class AddToCartAction
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, int $productId, TelegramUser $tgUser, string $callbackId): JsonResponse
    {
        $product = Product::find($productId);

        if (! $product || $product->stock_count < 1) {
            $this->context->telegram->answerCallbackQuery($callbackId, '❌ Sin stock disponible.', true);

            return $this->context->ok();
        }

        $cart = $tgUser->cart ?? [];
        $cart[] = ['id' => $product->id, 'name' => $product->name, 'price' => $product->discounted_price_usd];
        $tgUser->update(['cart' => $cart]);

        $this->context->telegram->sendMessage(
            $chatId,
            "✅ *{$product->name}* añadido al carrito.\n\n"
            .'Usa /carrito para ver tu carrito y finalizar tu compra con NT de forma segura.',
            TelegramService::inlineKeyboard([
                [['text' => '🛒 Ver carrito y Pagar', 'callback_data' => 'cmd_carrito']],
                [['text' => 'Seguir comprando', 'callback_data' => 'cmd_catalogo']],
            ])
        );

        return $this->context->ok();
    }
}
