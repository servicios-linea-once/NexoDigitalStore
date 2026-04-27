<?php

namespace App\Telegram\Commands;

use App\Models\Product;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class CatalogCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser): JsonResponse
    {
        $platforms = Product::query()
            ->where('status', 'active')
            ->where('stock_count', '>', 0)
            ->distinct()
            ->pluck('platform');

        if ($platforms->isEmpty()) {
            $this->context->telegram->sendMessage($chatId, '😔 No hay productos disponibles en este momento.');

            return $this->context->ok();
        }

        $rows = [];
        $currentRow = [];

        foreach ($platforms as $platform) {
            $currentRow[] = ['text' => "🎮 {$platform}", 'callback_data' => 'cat_'.substr($platform, 0, 20)];

            if (count($currentRow) === 2) {
                $rows[] = $currentRow;
                $currentRow = [];
            }
        }

        if ($currentRow !== []) {
            $rows[] = $currentRow;
        }

        $this->context->telegram->sendMessage(
            $chatId,
            "🛍️ *Catálogo por Categorías*\n\nSelecciona una plataforma para ver sus productos:",
            TelegramService::inlineKeyboard($rows)
        );

        return $this->context->ok();
    }
}
