<?php

namespace App\Telegram\Commands;

use App\Models\Product;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class PromotionsCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser): JsonResponse
    {
        $products = Product::active()
            ->inStock()
            ->whereHas('promotions', function ($query) {
                $query->where('is_active', true)
                    ->where(function ($dates) {
                        $dates->whereNull('start_date')->orWhere('start_date', '<=', now());
                    })
                    ->where(function ($dates) {
                        $dates->whereNull('end_date')->orWhere('end_date', '>=', now());
                    });
            })
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            $this->context->telegram->sendMessage($chatId, 'En este momento no hay productos en promoción.');

            return $this->context->ok();
        }

        $rows = $products->map(fn ($product) => [[
            'text' => "🔥 {$product->name} — \${$product->discounted_price_usd} USD",
            'callback_data' => "product_{$product->id}",
        ]])->toArray();

        $this->context->telegram->sendMessage(
            $chatId,
            "🔥 *Mejores Ofertas Actuales* 🔥\n",
            TelegramService::inlineKeyboard($rows)
        );

        return $this->context->ok();
    }
}
