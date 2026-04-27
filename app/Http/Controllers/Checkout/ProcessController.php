<?php

namespace App\Http\Controllers\Checkout;

use App\Exceptions\Checkout\InsufficientBalanceException;
use App\Exceptions\Checkout\InvalidCurrencyException;
use App\Exceptions\Checkout\OutOfStockException;
use App\Exceptions\Checkout\UnavailableKeyException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\ProcessCheckoutRequest;
use App\Models\Currency;
use App\Models\DigitalKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function __invoke(ProcessCheckoutRequest $request, CartService $cartService): RedirectResponse
    {
        $cartItems = $cartService->getItems($request);

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
        }

        $user = $request->user();

        // ── Anti-Fraud Check ────────────────────────────────────────────────
        $recentPending = Order::where('buyer_id', $user->id)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        if ($recentPending >= 3) {
            return back()->withErrors(['error' => 'Tienes demasiadas órdenes pendientes en las últimas 24 h. Completa o cancela tus órdenes actuales.']);
        }

        $recentFailed = Order::where('buyer_id', $user->id)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recentFailed >= 3) {
            return back()->withErrors(['error' => 'Demasiados intentos fallidos. Tu cuenta ha sido bloqueada temporalmente.']);
        }

        try {
            // Toda la lógica en una sola transacción segura
            $order = DB::transaction(function () use ($request, $cartItems, $user) {
                return $this->createOrder($request, $cartItems, $user);
            });

            $cartService->clear($request);

            if ($request->payment_method === 'nexotokens') {
                return redirect()->route('orders.show', $order->ulid)
                    ->with('success', '¡Pago completado con NexoTokens! Tus claves están disponibles.');
            }

            return redirect()->route('orders.show', $order->ulid)
                ->with('info', 'Orden creada. Completa el pago con '.ucfirst($request->payment_method).'.');

        } catch (InsufficientBalanceException $e) {
            Log::warning('Checkout failed: Insufficient balance', [
                'user_id' => $user->id,
                'required' => $e->getRequired(),
                'available' => $e->getAvailable(),
            ]);

            return back()->withErrors(['error' => $e->getShortMessage()]);

        } catch (OutOfStockException $e) {
            Log::warning('Checkout failed: Out of stock', [
                'user_id' => $user->id,
                'product' => $e->getProductName(),
            ]);

            return back()->withErrors(['error' => $e->getShortMessage()]);

        } catch (UnavailableKeyException $e) {
            Log::warning('Checkout failed: Unavailable key', [
                'user_id' => $user->id,
                'product' => $e->getProductName(),
            ]);

            return back()->withErrors(['error' => $e->getShortMessage()]);

        } catch (InvalidCurrencyException $e) {
            Log::error('Checkout failed: Invalid currency', [
                'user_id' => $user->id,
                'currency' => $e->getCurrency(),
            ]);

            return back()->withErrors(['error' => $e->getShortMessage()]);

        } catch (Throwable $e) {
            Log::critical('Checkout failed with critical error', [
                'user_id' => $user->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Error procesando el pago. Por favor intenta de nuevo.']);
        }
    }

    // ── Lógica Centralizada y Optimizada de Creación de Orden ────────────
    private function createOrder(Request $request, array $cartItems, $user): Order
    {
        $currency    = $request->currency ?? 'USD';
        $ntUsed      = (float) ($request->nt_amount ?? 0);
        $ntRateUsd   = config('nexo.token.rate_to_usd', 0.10);
        $ntDiscountUsd = round($ntUsed * $ntRateUsd, 4);
        $isNexoTokens  = $request->payment_method === 'nexotokens';

        // Opt 1: Bloqueamos la billetera UNA SOLA VEZ al inicio si se necesita
        $wallet = null;
        if ($user->wallet && ($ntUsed > 0 || $isNexoTokens)) {
            $wallet = $this->walletService->lockForWrite($user->wallet->id);

            if ($ntUsed > 0 && ! $wallet->hasSufficientBalance($ntUsed)) {
                throw new InsufficientBalanceException($ntUsed, $wallet->balance);
            }
        }

        $subtotalUsd = array_sum(array_column($cartItems, 'discounted_price_usd'));

        if ($currency === 'USD') {
            $subtotalInCurrency = $subtotalUsd;
            $rate = 1.0;
        } elseif ($currency === 'PEN') {
            $subtotalInCurrency = array_sum(array_column($cartItems, 'discounted_price_pen'));
            $rate = Currency::where('code', 'PEN')->value('rate_to_usd') ?? 1.0;
        } else {
            $currencyRecord = Currency::where('code', $currency)->first();
            if (!$currencyRecord) {
                throw new InvalidCurrencyException($currency);
            }
            $rate = $currencyRecord->rate_to_usd;
            $subtotalInCurrency = Currency::convert($subtotalUsd, 'USD', $currency);
        }

        $subDiscountPct        = $user->subscriptionDiscount();
        $subDiscountUsd        = $subDiscountPct > 0 ? round($subtotalUsd * $subDiscountPct / 100, 4) : 0.0;
        $subDiscountInCurrency = $subDiscountPct > 0 ? round($subtotalInCurrency * $subDiscountPct / 100, 4) : 0.0;

        $totalUsd             = max(0, $subtotalUsd - $subDiscountUsd - $ntDiscountUsd);
        $ntDiscountInCurrency = $currency === 'USD' ? $ntDiscountUsd : Currency::convert($ntDiscountUsd, 'USD', $currency);
        $totalInCurrency      = max(0, $subtotalInCurrency - $subDiscountInCurrency - $ntDiscountInCurrency);

        $order = Order::create([
            'buyer_id'         => $user->id,
            'status'           => $isNexoTokens ? 'processing' : 'pending',
            'currency'         => $currency,
            'subtotal'         => $subtotalUsd,
            'discount_amount'  => $subDiscountUsd,
            'nexocoins_used'   => $ntUsed,
            'total'            => $totalUsd,
            'total_in_currency'=> $totalInCurrency,
            'exchange_rate'    => $rate,
            'payment_method'   => $request->payment_method,
            'payment_reference'=> $request->payment_reference,
            'ip_address'       => $request->ip(),
            'meta'             => $subDiscountPct > 0 ? [
                'subscription_plan'     => $user->activeSubscription?->plan?->name,
                'subscription_discount' => $subDiscountPct,
            ] : null,
        ]);

        $productIds = array_column($cartItems, 'id');
        $products   = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

        // Opt 2: Instanciar tiempo y acumuladores fuera del bucle
        $now = now();
        $totalCashbackNt = 0;

        foreach ($cartItems as $cartItem) {
            $product = $products->get($cartItem['id']);

            if (! $product || $product->stock_count < 1) {
                throw new OutOfStockException($cartItem['name']);
            }

            $key = DigitalKey::where('product_id', $product->id)->where('status', 'available')->lockForUpdate()->first();

            if (! $key) {
                throw new UnavailableKeyException($cartItem['name']);
            }

            $itemPriceUsd = $cartItem['discounted_price_usd'];
            $cashbackPercent = $cartItem['cashback_percent'] ?? 0;
            // Opt 3: Corrección de jerarquía matemática
            $cashbackNt = $cartItem['cashback_amount_nt'] > 0
                ? $cartItem['cashback_amount_nt']
                : round(($itemPriceUsd * ($cashbackPercent / 100)) / $ntRateUsd, 4);

            $orderItem = OrderItem::create([
                'order_id'         => $order->id,
                'product_id'       => $product->id,
                'seller_id'        => $product->seller_id,
                'product_name'     => $product->name,
                'product_cover'    => $cartItem['cover_image'],
                'quantity'         => 1,
                'unit_price'       => $itemPriceUsd,
                'total_price'      => $itemPriceUsd,
                'cashback_amount'  => $cashbackNt,
                'delivery_status'  => 'pending',
            ]);

            // Siempre se descuenta el stock
            $product->decrement('stock_count');

            if ($isNexoTokens) {
                $isAuto = $product->delivery_type === 'auto';

                $key->update(['status' => 'sold', 'order_item_id' => $orderItem->id, 'sold_at' => $now]);

                if ($isAuto) {
                    $orderItem->update(['delivery_status' => 'delivered', 'delivered_at' => $now]);
                }

                $product->increment('total_sales');

                // Sumamos al acumulador en lugar de grabar en BD de inmediato
                if ($cashbackNt > 0) {
                    $totalCashbackNt += $cashbackNt;
                }
            } else {
                $key->update([
                    'status'         => 'reserved',
                    'order_item_id'  => $orderItem->id,
                    'reserved_at'    => $now,
                    'reserved_until' => $now->copy()->addMinutes(config('nexo.inventory.reservation_minutes', 15)),
                ]);
            }
        }

        // Opt 4: Procesamiento de operaciones de billetera (Deducciones y Agrupación de Cashback)
        if ($wallet) {
            if ($ntUsed > 0) {
                $this->walletService->debit($wallet, $ntUsed, 'purchase', "NT usado en orden #{$order->ulid}", "Order:{$order->id}");
                $wallet->refresh(); // Mantenemos el estado fresco en memoria
            }

            if ($totalCashbackNt > 0) {
                $this->walletService->credit($wallet, $totalCashbackNt, 'cashback', "Cashback total por orden #{$order->ulid}", "Order:{$order->id}");
            }
        }

        if ($isNexoTokens) {
            $order->update(['status' => 'completed', 'completed_at' => $now]);
        }

        return $order;
    }
}
