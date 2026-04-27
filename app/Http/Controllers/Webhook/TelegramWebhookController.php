<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\TelegramUser;
use App\Telegram\Callbacks\AddToCartAction;
use App\Telegram\Callbacks\CheckoutNtAction;
use App\Telegram\Callbacks\ClearCartAction;
use App\Telegram\Callbacks\ConfirmCheckoutAction;
use App\Telegram\Callbacks\ShowCategoryProductsAction;
use App\Telegram\Callbacks\ShowProductAction;
use App\Telegram\Commands\BalanceCommand;
use App\Telegram\Commands\CartCommand;
use App\Telegram\Commands\CatalogCommand;
use App\Telegram\Commands\HelpCommand;
use App\Telegram\Commands\LinkCommand;
use App\Telegram\Commands\OrdersCommand;
use App\Telegram\Commands\PromotionsCommand;
use App\Telegram\Commands\SearchCommand;
use App\Telegram\Commands\StartCommand;
use App\Telegram\Commands\UnlinkCommand;
use App\Telegram\State\HandleStateAction;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $secret = config('nexo.telegram.webhook_secret', config('services.telegram.webhook_secret', ''));
        if (! empty($secret)) {
            $receivedToken = $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($secret, $receivedToken)) {
                Log::warning('[Telegram] Webhook secret mismatch — request rejected.', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['ok' => false], 403);
            }
        }

        $update = $request->all();

        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query']);
        }

        if (isset($update['message'])) {
            return $this->handleMessage($update['message']);
        }

        return $this->context->ok();
    }

    protected function handleMessage(array $message): JsonResponse
    {
        $from = $message['from'];
        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');

        $tgUser = TelegramUser::updateOrCreate(
            ['telegram_id' => (string) $from['id']],
            [
                'first_name' => $from['first_name'] ?? '',
                'last_name' => $from['last_name'] ?? '',
                'username' => $from['username'] ?? null,
                'language_code' => $from['language_code'] ?? 'es',
                'last_interaction_at' => now(),
            ]
        );

        if (str_starts_with($text, '/')) {
            return $this->handleCommand($chatId, $text, $tgUser);
        }

        if ($tgUser->state && $tgUser->state !== 'idle') {
            return app(HandleStateAction::class)($chatId, $text, $tgUser);
        }

        return app(SearchCommand::class)($chatId, $text, $tgUser);
    }

    protected function handleCommand(int $chatId, string $text, TelegramUser $tgUser): JsonResponse
    {
        $parts = explode(' ', $text, 2);
        $command = strtolower(explode('@', $parts[0])[0]);
        $args = $parts[1] ?? '';

        return match ($command) {
            '/start' => app(StartCommand::class)($chatId, $args, $tgUser),
            '/vincular' => app(LinkCommand::class)($chatId, $args, $tgUser),
            '/desvincular' => app(UnlinkCommand::class)($chatId, $tgUser),
            '/catalogo', '/productos' => app(CatalogCommand::class)($chatId, $tgUser),
            '/buscar' => app(SearchCommand::class)($chatId, $args, $tgUser),
            '/carrito' => app(CartCommand::class)($chatId, $tgUser),
            '/pedidos' => app(OrdersCommand::class)($chatId, $tgUser),
            '/saldo' => app(BalanceCommand::class)($chatId, $tgUser),
            '/promociones' => app(PromotionsCommand::class)($chatId, $tgUser),
            '/ayuda', '/help' => app(HelpCommand::class)($chatId, $tgUser),
            default => $this->unknownCommand($chatId),
        };
    }

    protected function handleCallback(array $callback): JsonResponse
    {
        $chatId = $callback['message']['chat']['id'];
        $data = $callback['data'];
        $callbackId = $callback['id'];

        $tgUser = TelegramUser::where('telegram_id', (string) $callback['from']['id'])->first();
        if (! $tgUser) {
            $this->context->telegram->answerCallbackQuery($callbackId, '⚠️ Inicia el bot primero con /start');

            return $this->context->ok();
        }

        $this->context->telegram->answerCallbackQuery($callbackId);

        if ($data === 'cmd_catalogo') {
            return app(CatalogCommand::class)($chatId, $tgUser);
        }

        if ($data === 'cmd_carrito') {
            return app(CartCommand::class)($chatId, $tgUser);
        }

        if ($data === 'cmd_vincular') {
            return app(LinkCommand::class)($chatId, '', $tgUser);
        }

        if ($data === 'cmd_ayuda') {
            return app(HelpCommand::class)($chatId, $tgUser);
        }

        if ($data === 'cart_clear') {
            return app(ClearCartAction::class)($chatId, $tgUser);
        }

        if (str_starts_with($data, 'cat_')) {
            return app(ShowCategoryProductsAction::class)($chatId, substr($data, 4), $tgUser);
        }

        if (str_starts_with($data, 'product_')) {
            return app(ShowProductAction::class)($chatId, (int) substr($data, 8), $tgUser);
        }

        if (str_starts_with($data, 'add_to_cart_')) {
            return app(AddToCartAction::class)($chatId, (int) substr($data, 12), $tgUser, $callbackId);
        }

        if ($data === 'checkout_nt') {
            return app(ConfirmCheckoutAction::class)($chatId, $tgUser, $callbackId);
        }

        if ($data === 'checkout_confirm_nt') {
            return app(CheckoutNtAction::class)($chatId, $tgUser, $callbackId);
        }

        return $this->context->ok();
    }

    protected function unknownCommand(int $chatId): JsonResponse
    {
        $this->context->telegram->sendMessage(
            $chatId,
            'Comando desconocido. Usa /ayuda para ver los disponibles.'
        );

        return $this->context->ok();
    }
}
