<?php

namespace App\Telegram\Commands;

use App\Models\Product;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class SearchCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, string $query, TelegramUser $tgUser): JsonResponse
    {
        if (strlen(trim($query)) < 2) {
            $this->context->telegram->sendMessage(
                $chatId,
                "🔍 Uso: `/buscar steam wallet`\n\nEscribe al menos 2 caracteres."
            );

            return $this->context->ok();
        }

        $products = Product::active()
            ->inStock()
            ->search($query)
            ->limit(5)
            ->get(['id', 'name', 'price_usd', 'stock_count']);

        if ($products->isEmpty()) {
            $this->context->telegram->sendMessage(
                $chatId,
                "🔍 Sin resultados para *\"{$query}\"*.\n\nPrueba con otro término."
            );

            return $this->context->ok();
        }

        $rows = $products->map(fn ($product) => [[
            'text' => "🛒 {$product->name} (Stock: {$product->stock_count}) — \${$product->discounted_price_usd} USD",
            'callback_data' => "product_{$product->id}",
        ]])->toArray();

        $this->context->telegram->sendMessage(
            $chatId,
            "🔍 Resultados para *\"{$query}\"*:",
            TelegramService::inlineKeyboard($rows)
        );

        return $this->context->ok();
    }
}
