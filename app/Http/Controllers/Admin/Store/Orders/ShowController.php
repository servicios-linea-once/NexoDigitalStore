<?php

namespace App\Http\Controllers\Admin\Store\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * GET /admin/store/orders/{ulid}
 */
class ShowController extends Controller
{
    public function __invoke(Request $request, string $ulid): Response
    {
        $order = Order::where('ulid', $ulid)
            ->with([
                'buyer:id,name,email',
                'items.product:id,name,ulid',
            ])
            ->firstOrFail();

        return Inertia::render('Admin/Store/Orders/Show', [
            'order' => [
                'id'               => $order->id,
                'ulid'             => $order->ulid,
                'status'           => $order->status,
                'buyer'            => $order->buyer ? ['name' => $order->buyer->name, 'email' => $order->buyer->email] : null,
                'created_at'       => $order->created_at->toIso8601String(),
                'paid_at'          => $order->paid_at?->toIso8601String(),
                'payment_method'   => $order->payment_method,
                'items'            => $order->items->map(fn ($item) => [
                    'id'                => $item->id,
                    'product_name'      => $item->product_name ?? $item->product?->name,
                    'product_ulid'      => $item->product?->ulid,
                    'unit_price'        => (float) $item->unit_price,
                    'commission_rate'   => (float) $item->commission_rate,
                    'commission_amount' => (float) $item->commission_amount,
                    'seller_earnings'   => (float) $item->seller_earnings,
                    'cashback_amount'   => (float) ($item->cashback_amount ?? 0),
                    'delivery_status'   => $item->delivery_status,
                ]),
                'total_earnings'   => (float) $order->items->sum('seller_earnings'),
                'total_commission' => (float) $order->items->sum('commission_amount'),
            ],
        ]);
    }
}
