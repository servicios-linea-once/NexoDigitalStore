<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    public function __invoke(Request $request, string $ulid): Response
    {
        $order = Order::where('ulid', $ulid)
            ->where('buyer_id', $request->user()->id)
            ->with([
                'items.product:id,slug,name,platform,region',
                'items.digitalKey:id,order_item_id,key_value,license_type,max_activations',
                'payments',
            ])
            ->firstOrFail();

        $isCompleted = $order->isCompleted();

        // Extraemos las claves solo si la orden está completada
        $items = $order->items->map(function ($item) use ($isCompleted) {
            $revealedKey = null;

            if ($isCompleted && $item->digitalKey) {
                try {
                    // El mutator de Laravel desencripta automáticamente el AES-256
                    $revealedKey = $item->digitalKey->key_value;
                } catch (\Exception) {
                    $revealedKey = null;
                }
            }

            return [
                'id'              => $item->id,
                'ulid'            => $item->ulid,
                'product_name'    => $item->product_name,
                'product_cover'   => $item->product_cover,
                'product_slug'    => $item->product?->slug,
                'platform'        => $item->product?->platform,
                'region'          => $item->product?->region,
                'unit_price'      => (float) $item->unit_price,
                'cashback_amount' => (float) $item->cashback_amount,
                'delivery_status' => $item->delivery_status,
                'delivered_at'    => $item->delivered_at?->format('d/m/Y H:i'),
                'revealed_key'    => $revealedKey,
                'license_type'    => $item->digitalKey?->license_type,
            ];
        });

        // Configuración de los gateways leída desde los archivos config nativos
        $ppMode = config('paypal.mode', 'sandbox');
        $ppClientId = $ppMode === 'sandbox'
            ? config('paypal.sandbox.client_id')
            : config('paypal.live.client_id');

        return Inertia::render('Orders/Show', [
            'order' => [
                'id'                => $order->id,
                'ulid'              => $order->ulid,
                'status'            => $order->status,
                'subtotal'          => (float) $order->subtotal,
                'discount_amount'   => (float) ($order->discount_amount ?? 0),
                'nexocoins_used'    => (float) $order->nexocoins_used,
                'total'             => (float) $order->total,
                'total_in_currency' => (float) ($order->total_in_currency ?? $order->total),
                'exchange_rate'     => (float) ($order->exchange_rate ?? 1),
                'currency'          => $order->currency,
                'payment_method'    => $order->payment_method,
                'payment_reference' => $order->payment_reference,
                'meta'              => $order->meta,
                'completed_at'      => $order->completed_at?->format('d/m/Y H:i'),
                'created_at'        => $order->created_at->format('d/m/Y H:i'),
                'is_pending'        => $order->status === 'pending',
                'is_completed'      => $isCompleted,
            ],
            'items'          => $items,
            'paypalClientId' => $ppClientId,
            'paypalMode'     => $ppMode,
            'mpPublicKey'    => config('mercadopago.public_key'),
        ]);
    }
}
