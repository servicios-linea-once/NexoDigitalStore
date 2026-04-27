<?php

namespace App\Telegram\Commands;

use App\Models\Order;
use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class OrdersCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser): JsonResponse
    {
        if (! $tgUser->is_linked) {
            return $this->context->requireLinked($chatId);
        }

        $orders = Order::query()
            ->where('buyer_id', $tgUser->user_id)
            ->latest()
            ->limit(5)
            ->get();

        if ($orders->isEmpty()) {
            $this->context->telegram->sendMessage(
                $chatId,
                "📦 No tienes pedidos aún.\n\nExplora el catálogo con /catalogo."
            );

            return $this->context->ok();
        }

        $text = "📦 *Tus últimos pedidos:*\n\n";

        foreach ($orders as $order) {
            $id = strtoupper(substr($order->ulid, -8));
            $status = $this->context->statusEmoji($order->status).' '.ucfirst($order->status);
            $total = number_format($order->total, 2);
            $date = $order->created_at->format('d/m/Y');
            $text .= "🔖 `#{$id}` — \${$total}\n   {$status} · {$date}\n\n";
        }

        $this->context->telegram->sendMessage($chatId, $text);

        return $this->context->ok();
    }
}
