<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;

class BalanceCommand
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser): JsonResponse
    {
        if (! $tgUser->is_linked) {
            return $this->context->requireLinked($chatId);
        }

        $wallet = $tgUser->user?->wallet;
        $ntRate = config('nexo.token.rate_to_usd', 0.10);
        $nt = number_format($wallet?->balance ?? 0, 2);
        $usd = number_format(($wallet?->balance ?? 0) * $ntRate, 2);

        $this->context->telegram->sendMessage(
            $chatId,
            "💰 *Saldo NexoTokens (NT)*\n\n"
            ."Disponible: *{$nt} NT*\n"
            ."Equivale a: *\${$usd} USD*\n\n"
            .'_1 NT = $0.10 USD — ganas NT con cada compra completada._'
        );

        return $this->context->ok();
    }
}
