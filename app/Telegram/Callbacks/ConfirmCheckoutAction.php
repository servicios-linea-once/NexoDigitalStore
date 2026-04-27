<?php

namespace App\Telegram\Callbacks;

use App\Models\TelegramUser;
use App\Services\TelegramService;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class ConfirmCheckoutAction
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser, string $callbackId): JsonResponse
    {
        $cart = $tgUser->cart ?? [];

        if (empty($cart)) {
            $this->context->telegram->answerCallbackQuery($callbackId, 'Tu carrito está vacío', true);

            return $this->context->ok();
        }

        $totalUsd = array_sum(array_column($cart, 'price'));
        $ntRate = config('nexo.token.rate_to_usd', 0.10);
        $totalNt = round($totalUsd / $ntRate);

        $this->context->telegram->sendMessage(
            $chatId,
            "⚠️ *Confirmación de compra*\n\n"
            ."¿Estás seguro de que deseas gastar *{$totalNt} NT*?\n"
            ."Esta acción no se puede deshacer.",
            TelegramService::inlineKeyboard([
                [['text' => '✅ Sí, confirmar pago', 'callback_data' => 'checkout_confirm_nt']],
                [['text' => '❌ Cancelar', 'callback_data' => 'cmd_carrito']],
            ])
        );

        return $this->context->ok();
    }
}
