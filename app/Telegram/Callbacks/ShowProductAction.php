<?php

namespace App\Telegram\Callbacks;

use App\Models\Product;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class ShowProductAction
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, int $productId, TelegramUser $tgUser): JsonResponse
    {
        $product = Product::query()->where('id', $productId)->where('status', 'active')->first();

        if (! $product) {
            $this->context->telegram->sendMessage($chatId, '❌ Producto no encontrado o sin stock.');

            return $this->context->ok();
        }

        $price = number_format($product->discounted_price_usd, 2);
        $stock = $product->stock_count > 0 ? "✅ En stock ({$product->stock_count})" : '❌ Sin stock';
        $promo = $product->active_promotion;
        $discount = $promo ? "\n🏷️ Descuento aplicado: *{$promo->name}*" : '';

        $cashback = '';
        if ($product->cashback_amount_nt > 0) {
            $cashback = "\n💰 Cashback: *{$product->cashback_amount_nt} NT*";
        } elseif ($product->cashback_percent > 0) {
            $cashback = "\n💰 Cashback: *{$product->cashback_percent}% NT*";
        }

        $text = "🎮 *{$product->name}*\n\n"
            ."🌍 Plataforma: *{$product->platform}* · Región: *{$product->region}*\n"
            ."💵 Precio: *\${$price} USD*{$discount}{$cashback}\n"
            ."📦 {$stock}\n\n"
            .($product->short_description ?? '');

        $buttons = [];

        if ($product->stock_count > 0) {
            $buttons[] = [['text' => '🛒 Añadir al carrito', 'callback_data' => "add_to_cart_{$productId}"]];
            $buttons[] = [['text' => '💳 Comprar en la web', 'url' => url("/products/{$product->slug}")]];
        } else {
            $buttons[] = [['text' => '🌐 Ver en la web', 'url' => url("/products/{$product->slug}")]];
        }

        $buttons[] = [['text' => '↩️ Volver al catálogo', 'callback_data' => 'cmd_catalogo']];

        if ($product->cover_image) {
            $this->context->telegram->sendPhoto($chatId, $product->cover_image, $text, TelegramService::inlineKeyboard($buttons));
        } else {
            $this->context->telegram->sendMessage($chatId, $text, TelegramService::inlineKeyboard($buttons));
        }

        return $this->context->ok();
    }
}
