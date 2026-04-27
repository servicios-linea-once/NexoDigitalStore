<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateOrderRequest;
use App\Models\DigitalKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['items.product', 'payments'])
            ->where('buyer_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        $data = $orders->map(fn ($order) => $this->formatOrderSummary($order));

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $ulid): JsonResponse
    {
        $order = Order::with(['items.product.coverImage', 'payments'])
            ->where('ulid', $ulid)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'order' => $this->formatOrderDetail($order),
        ]);
    }

    /**
     * Create a new order via API (NT payment only)
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $currency = $request->input('currency', 'USD');

        // Fetch products and validate stock
        $productIds = collect($request->input('items'))->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)
            ->active()
            ->inStock()
            ->with(['coverImage', 'promotions'])
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Uno o más productos no están disponibles.',
                'errors' => [
                    'products' => ['Uno o más productos no existen o no están disponibles.'],
                ],
            ], 400);
        }

        return DB::transaction(function () use ($request, $user, $currency, $products) {
            $items = $request->input('items');
            $subtotalUsd = 0.0;
            $subtotalPen = 0.0;
            $orderItems = [];

            foreach ($items as $itemData) {
                $product = $products->get($itemData['product_id']);
                $quantity = $itemData['quantity'] ?? 1;

                if ($product->stock_count < $quantity) {
                    throw new \Exception("Stock insuficiente para: {$product->name}");
                }

                $priceUsd = $product->discounted_price_usd;
                $pricePen = $product->discounted_price_pen;
                $cashbackNt = $product->cashback_amount_nt * $quantity;

                $orderItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price_usd' => $priceUsd,
                    'price_pen' => $pricePen,
                    'cashback_nt' => $cashbackNt,
                ];

                $subtotalUsd += $priceUsd * $quantity;
                $subtotalPen += $pricePen * $quantity;
            }

            // Calculate subscription discount
            $subDiscountPct = $user->subscriptionDiscount();
            $subDiscountUsd = $subDiscountPct > 0 ? round($subtotalUsd * $subDiscountPct / 100, 2) : 0.0;
            $subDiscountPen = $subDiscountPct > 0 ? round($subtotalPen * $subDiscountPct / 100, 2) : 0.0;

            $totalUsd = $subtotalUsd - $subDiscountUsd;
            $totalPen = $subtotalPen - $subDiscountPen;
            $exchangeRate = config('nexo.currency.exchange_rate_usd_to_pen', 3.7);

            // Calculate NT equivalent (only payment method)
            $nexoRate = config('nexo.token.rate_to_usd', 0.10);
            $totalNt = $totalUsd / $nexoRate;

            // Get wallet info
            $wallet = $user->wallet;
            $walletBalanceNt = $wallet?->balance ?? 0;
            $walletAvailableNt = $wallet?->available_balance ?? 0;

            // Check NT balance for immediate payment
            $hasEnoughBalance = $walletAvailableNt >= $totalNt;

            // Create order (status pending until payment)
            $order = Order::create([
                'buyer_id' => $user->id,
                'status' => 'pending',
                'subtotal' => $subtotalUsd,
                'discount_amount' => $subDiscountUsd,
                'nexocoins_used' => 0,
                'total' => $totalUsd,
                'currency' => $currency,
                'total_in_currency' => $currency === 'USD' ? $totalUsd : $totalPen,
                'exchange_rate' => $exchangeRate,
                'payment_method' => 'nexotokens',
                'ip_address' => $request->ip(),
                'meta' => [
                    'source' => 'api',
                    'total_nt' => $totalNt,
                    'nexo_coins_equivalent' => $totalNt,
                    'subtotal_pen' => $subtotalPen,
                    'subtotal_usd' => $subtotalUsd,
                    'sub_discount_pct' => $subDiscountPct,
                    'sub_discount_usd' => $subDiscountUsd,
                    'sub_discount_pen' => $subDiscountPen,
                ],
            ]);

            // Reserve stock
            foreach ($orderItems as $itemData) {
                $product = $itemData['product'];
                $product->decrement('stock_count', $itemData['quantity']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_name' => $product->name,
                    'product_cover' => $product->coverImage?->url,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['price_usd'],
                    'total_price' => $itemData['price_usd'] * $itemData['quantity'],
                    'cashback_amount' => $itemData['cashback_nt'],
                    'delivery_status' => 'pending',
                ]);
            }

            Log::info("[API] Order created: {$order->ulid}", [
                'user_id' => $user->id,
                'total_usd' => $totalUsd,
                'total_nt' => $totalNt,
            ]);

            // Return order with payment info (NT only)
            return response()->json([
                'success' => true,
                'message' => 'Pedido creado. Completa el pago con NexoTokens.',
                'order' => $this->formatOrderDetail($order),
                'payment' => [
                    'method' => 'nexotokens',
                    'total_usd' => round($totalUsd, 2),
                    'total_pen' => round($totalPen, 2),
                    'total_nt' => round($totalNt, 2),
                    'exchange_rate_nt' => $nexoRate,
                    'wallet_balance_nt' => round($walletBalanceNt, 2),
                    'wallet_available_nt' => round($walletAvailableNt, 2),
                    'has_sufficient_balance' => $hasEnoughBalance,
                    'savings_usd' => round($subDiscountUsd, 2),
                    'savings_pen' => round($subDiscountPen, 2),
                ],
                'expires_at' => $order->created_at->addMinutes(30)->toIso8601String(),
            ], 201);
        });
    }

    /**
     * Pay order with NexoTokens only
     */
    public function pay(Request $request, string $ulid): JsonResponse
    {
        $request->validate([
            'nt_amount' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $order = Order::with(['items.product'])
            ->where('ulid', $ulid)
            ->where('buyer_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Check if order has expired (30 min)
        if ($order->created_at->addMinutes(30)->isPast()) {
            $this->expireOrder($order);

            return response()->json([
                'success' => false,
                'message' => 'El pedido ha expirado.',
                'order' => $this->formatOrderSummary($order->fresh()),
            ], 410);
        }

        $wallet = $request->user()->wallet;
        $totalNt = $order->meta['total_nt'] ?? ($order->total / config('nexo.token.rate_to_usd', 0.10));

        if (! $wallet || $wallet->available_balance < $totalNt) {
            return response()->json([
                'success' => false,
                'message' => 'Saldo insuficiente en tu billetera de NexoTokens.',
                'payment' => [
                    'required_nt' => round($totalNt, 2),
                    'available_nt' => round($wallet?->available_balance ?? 0, 2),
                    'deficit_nt' => round(max(0, $totalNt - ($wallet?->available_balance ?? 0)), 2),
                ],
            ], 400);
        }

        return DB::transaction(function () use ($order, $wallet, $totalNt, $request) {
            // Debit from wallet
            $this->walletService->debit(
                $wallet,
                $totalNt,
                'purchase',
                "Compra #{$order->ulid}",
                "Order:{$order->id}"
            );

            // Update order status
            $order->update([
                'status' => 'completed',
                'nexocoins_used' => $totalNt,
                'paid_at' => now(),
                'completed_at' => now(),
            ]);

            // Deliver digital keys
            $deliveredKeys = $this->deliverDigitalKeys($order);

            Log::info("[API] Order paid with NT: {$order->ulid}", [
                'total_nt' => $totalNt,
                'delivered_keys' => count($deliveredKeys),
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Pago completado! Tus claves digitales han sido entregadas.',
                'order' => $this->formatOrderDetail($order->fresh()),
                'wallet' => [
                    'new_balance_nt' => round($wallet->fresh()->balance, 2),
                    'debited_nt' => round($totalNt, 2),
                ],
                'delivered_keys' => $deliveredKeys,
            ]);
        });
    }

    /**
     * Get order payment status
     */
    public function paymentStatus(string $ulid): JsonResponse
    {
        $order = Order::where('ulid', $ulid)
            ->where('buyer_id', auth('sanctum')->id())
            ->firstOrFail();

        $totalNt = $order->meta['total_nt'] ?? 0;
        $isExpired = $order->status === 'pending' && $order->created_at->addMinutes(30)->isPast();

        return response()->json([
            'success' => true,
            'order' => [
                'ulid' => $order->ulid,
                'status' => $isExpired ? 'expired' : $order->status,
                'payment_method' => 'nexotokens',
                'total_usd' => (float) $order->total,
                'total_nt' => round($totalNt, 2),
                'wallet_balance_nt' => round(auth('sanctum')->user()->wallet?->balance ?? 0, 2),
                'can_pay' => $order->status === 'pending' && !$isExpired,
                'is_expired' => $isExpired,
                'expires_at' => $order->created_at->addMinutes(30)->toIso8601String(),
            ],
        ]);
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    private function deliverDigitalKeys(Order $order): array
    {
        $delivered = [];

        foreach ($order->items as $item) {
            if ($item->product?->delivery_type === 'automatic') {
                // Find and deliver available key
                $key = DigitalKey::where('product_id', $item->product_id)
                    ->where('status', 'sold')
                    ->where('buyer_id', $order->buyer_id)
                    ->whereNull('delivered_at')
                    ->first();

                if ($key) {
                    $key->update(['delivered_at' => now()]);
                    $item->update([
                        'delivery_status' => 'delivered',
                        'delivered_at' => now(),
                    ]);

                    $delivered[] = [
                        'key_ulid' => $key->ulid,
                        'product_name' => $item->product_name,
                        'key_value' => $key->key_value,
                    ];
                }
            } else {
                $item->update([
                    'delivery_status' => 'processing',
                ]);
            }
        }

        return $delivered;
    }

    private function expireOrder(Order $order): void
    {
        $order->update(['status' => 'expired']);

        // Restore stock
        foreach ($order->items as $item) {
            $item->product?->increment('stock_count', $item->quantity);
        }
    }

    private function formatOrderSummary(Order $order): array
    {
        $totalNt = $order->meta['total_nt'] ?? 0;

        return [
            'ulid' => $order->ulid,
            'status' => $order->status,
            'total_usd' => (float) $order->total,
            'total_nt' => round($totalNt, 2),
            'currency' => $order->currency,
            'payment_method' => $order->payment_method,
            'item_count' => $order->items->count(),
            'items_preview' => $order->items->take(3)->map(fn ($i) => [
                'name' => $i->product_name,
                'cover' => $i->product_cover,
            ]),
            'created_at' => $order->created_at->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
        ];
    }

    private function formatOrderDetail(Order $order): array
    {
        $totalNt = $order->meta['total_nt'] ?? 0;
        $deliveredKeys = [];
        $pendingKeys = [];

        foreach ($order->items as $item) {
            $keyInfo = [
                'name' => $item->product_name,
                'cover' => $item->product_cover,
                'quantity' => $item->quantity,
                'unit_price_usd' => (float) $item->unit_price,
                'total_price_usd' => (float) $item->total_price,
                'cashback_nt' => (int) $item->cashback_amount,
                'delivery_status' => $item->delivery_status,
                'delivered_at' => $item->delivered_at?->toIso8601String(),
            ];

            if ($item->delivery_status === 'delivered' && $item->digitalKey) {
                $keyInfo['key'] = $item->digitalKey->key_value;
                $keyInfo['key_ulid'] = $item->digitalKey->ulid;
            }

            if ($item->delivery_status === 'delivered') {
                $deliveredKeys[] = $keyInfo;
            } else {
                $pendingKeys[] = $keyInfo;
            }
        }

        return [
            'ulid' => $order->ulid,
            'status' => $order->status,
            'subtotal_usd' => (float) $order->subtotal,
            'subtotal_pen' => (float) ($order->meta['subtotal_pen'] ?? 0),
            'discount_amount_usd' => (float) $order->discount_amount,
            'discount_amount_pen' => (float) ($order->meta['sub_discount_pen'] ?? 0),
            'nexocoins_used_nt' => (float) $order->nexocoins_used,
            'total_usd' => (float) $order->total,
            'total_pen' => (float) $order->total_in_currency,
            'total_nt' => round($totalNt, 2),
            'currency' => $order->currency,
            'exchange_rate' => (float) $order->exchange_rate,
            'payment_method' => $order->payment_method,
            'payment_reference' => $order->payment_reference,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
            'expires_at' => $order->created_at->addMinutes(30)->toIso8601String(),
            'items' => [
                'delivered' => $deliveredKeys,
                'pending' => $pendingKeys,
                'total_count' => $order->items->count(),
            ],
            'cashback_total_nt' => (int) $order->items->sum('cashback_amount'),
            'created_at' => $order->created_at->toIso8601String(),
        ];
    }
}
