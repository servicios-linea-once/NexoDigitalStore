<?php

namespace App\Telegram\Callbacks;

use App\Models\DigitalKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TelegramUser;
use App\Telegram\Support\TelegramBotContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutNtAction
{
    public function __construct(protected TelegramBotContext $context)
    {
    }

    public function __invoke(int $chatId, TelegramUser $tgUser, string $callbackId): JsonResponse
    {
        if (! $tgUser->is_linked) {
            return $this->context->requireLinked($chatId);
        }

        $cart = $tgUser->cart ?? [];
        if (empty($cart)) {
            $this->context->telegram->answerCallbackQuery($callbackId, '⚠️ Tu carrito está vacío.', true);

            return $this->context->ok();
        }

        $user = $tgUser->user;
        $missingStock = null;
        $keysDelivered = [];

        try {
            DB::transaction(function () use ($user, $cart, &$keysDelivered, &$missingStock) {
                $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
                $productCounts = [];
                $totalUsd = 0;

                foreach ($cart as $item) {
                    $productId = $item['id'];
                    $productCounts[$productId] = ($productCounts[$productId] ?? 0) + 1;
                    $totalUsd += $item['price'];
                }

                $ntRate = config('nexo.token.rate_to_usd', 0.10);
                $totalNt = round($totalUsd / $ntRate);

                if ($wallet->balance < $totalNt) {
                    throw new \Exception('insufficient_funds');
                }

                $products = Product::whereIn('id', array_keys($productCounts))->lockForUpdate()->get()->keyBy('id');

                foreach ($productCounts as $productId => $count) {
                    $product = $products->get($productId);
                    if (! $product || $product->stock_count < $count) {
                        $missingStock = $product ? $product->name : "Producto #$productId";
                        throw new \Exception('no_stock');
                    }
                }

                $order = Order::create([
                    'buyer_id' => $user->id,
                    'status' => 'completed',
                    'currency' => 'NT',
                    'subtotal' => $totalUsd,
                    'discount_amount' => 0,
                    'nexocoins_used' => $totalNt,
                    'total' => 0,
                    'total_in_currency' => 0,
                    'exchange_rate' => 1,
                    'payment_method' => 'nexotokens',
                    'completed_at' => now(),
                ]);

                foreach ($productCounts as $productId => $count) {
                    $product = $products->get($productId);
                    $product->decrement('stock_count', $count);
                    $product->increment('total_sales', $count);

                    for ($i = 0; $i < $count; $i++) {
                        $cashbackAmt = round($product->price_usd * ($product->cashback_percent / 100) / $ntRate, 4);

                        $orderItem = OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'seller_id' => $product->seller_id,
                            'product_name' => $product->name,
                            'product_cover' => $product->cover_image,
                            'quantity' => 1,
                            'unit_price' => $product->price_usd,
                            'total_price' => $product->price_usd,
                            'commission_rate' => 0.0,
                            'commission_amount' => 0.0,
                            'seller_earnings' => $product->price_usd,
                            'cashback_amount' => $cashbackAmt,
                            'delivery_status' => 'delivered',
                            'delivered_at' => now(),
                        ]);

                        $digitalKey = DigitalKey::where('product_id', $product->id)
                            ->where('status', 'available')
                            ->lockForUpdate()
                            ->first();

                        if ($digitalKey) {
                            $digitalKey->update([
                                'order_item_id' => $orderItem->id,
                                'status' => 'sold',
                                'sold_at' => now(),
                            ]);

                            $keysDelivered[] = [
                                'name' => $product->name,
                                'key' => $digitalKey->key_value,
                            ];
                        }

                        if ($cashbackAmt > 0) {
                            $freshWallet = $this->context->wallets->lockForWrite($wallet->id);
                            $this->context->wallets->credit(
                                $freshWallet,
                                $cashbackAmt,
                                'cashback',
                                "Cashback: {$product->name} (Bot)",
                                "OrderItem:{$orderItem->id}"
                            );
                            $wallet = $freshWallet->fresh();
                        }
                    }
                }

                $freshWallet = $this->context->wallets->lockForWrite($wallet->id);
                $this->context->wallets->debit(
                    $freshWallet,
                    $totalNt,
                    'purchase',
                    "NT usado en orden #{$order->ulid} (Bot)",
                    "Order:{$order->id}"
                );
            });

            $tgUser->update(['cart' => []]);

            $text = "🎉 *¡Compra completada con éxito!*\n\n";
            foreach ($keysDelivered as $delivered) {
                $text .= "🎮 *{$delivered['name']}*\n🔑 `{$delivered['key']}`\n\n";
            }
            $text .= '_Tus claves están seguras y también puedes verlas en tu cuenta web._';

            $this->context->telegram->sendMessage($chatId, $text);
            $this->context->telegram->answerCallbackQuery($callbackId, '¡Pago completado!');
        } catch (\Exception $exception) {
            $message = $exception->getMessage();

            if ($message === 'insufficient_funds') {
                $this->context->telegram->sendMessage(
                    $chatId,
                    "❌ No tienes suficientes NexoTokens (NT).\n\nRecarga tu saldo en la plataforma web y vuelve a intentarlo."
                );
            } elseif ($message === 'no_stock') {
                $this->context->telegram->sendMessage(
                    $chatId,
                    "❌ El producto *{$missingStock}* se ha quedado sin stock.\n\nPor favor, vacía tu carrito e inténtalo de nuevo."
                );
            } else {
                Log::error('Telegram Checkout Error: '.$exception->getMessage());
                $this->context->telegram->sendMessage(
                    $chatId,
                    '⚠️ Ocurrió un error al procesar tu pago. Inténtalo más tarde.'
                );
            }

            $this->context->telegram->answerCallbackQuery($callbackId, 'Error en el pago', true);
        }

        return $this->context->ok();
    }
}
