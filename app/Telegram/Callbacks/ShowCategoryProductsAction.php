<?php

namespace App\Telegram\Callbacks;

use App\Models\Product;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ShowCategoryProductsAction
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, string $platformPrefix, TelegramUser $tgUser): JsonResponse
    {
        $cacheKey = 'telegram:category:'.md5(mb_strtolower($platformPrefix));

        $payload = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($platformPrefix) {
            $products = Product::query()
                ->active()
                ->inStock()
                ->where('platform', 'like', $platformPrefix.'%')
                ->orderByDesc('is_featured')
                ->limit(10)
                ->get(['id', 'name', 'price_usd', 'platform', 'stock_count']);

            if ($products->isEmpty()) {
                return null;
            }

            $rows = [];
            $text = "🎮 *Productos de {$products->first()->platform}*\n\n";

            foreach ($products as $product) {
                $price = number_format($product->discounted_price_usd, 2);
                $promo = $product->active_promotion;
                $discount = $promo ? " ({$promo->name})" : '';
                $text .= "• *{$product->name}* — \${$price} USD{$discount}\n";
                $rows[] = [[
                    'text' => "🛒 {$product->name} (Stock: {$product->stock_count})",
                    'callback_data' => "product_{$product->id}",
                ]];
            }

            $rows[] = [['text' => '↩️ Volver a Categorías', 'callback_data' => 'cmd_catalogo']];

            return [
                'text' => $text,
                'rows' => $rows,
            ];
        });

        if (! $payload) {
            $this->context->telegram->sendMessage($chatId, '😔 No hay productos para esta categoría.');

            return $this->context->ok();
        }

        $this->context->telegram->sendMessage(
            $chatId,
            $payload['text'],
            TelegramService::inlineKeyboard($payload['rows'])
        );

        return $this->context->ok();
    }
}
