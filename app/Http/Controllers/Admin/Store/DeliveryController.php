<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\DeliverKeyRequest;
use App\Models\DigitalKey;
use App\Models\OrderItem;
// [PUNTO-1] EscrowService eliminado — Single-Vendor: la orden se completa directamente
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryController extends Controller
{
    // ── Pending deliveries queue ──────────────────────────────────────────
    public function index(Request $request): Response
    {
        $sellerId = $request->user()->id;

        $items = OrderItem::where('seller_id', $sellerId)
            ->where('delivery_type', 'manual')
            ->whereIn('delivery_status', ['pending', 'failed'])
            ->with(['order:id,ulid,buyer_id,status', 'order.buyer:id,name,email'])
            ->orderBy('created_at')
            ->paginate(20)
            ->through(fn ($item) => [
                'id'               => $item->id,
                'ulid'             => $item->ulid,
                'order_ulid'       => $item->order?->ulid,
                'buyer_name'       => $item->order?->buyer?->name ?? '—',
                'buyer_email'      => $item->order?->buyer?->email ?? '—',
                'product_name'     => $item->product_name,
                'quantity'         => $item->quantity,
                'delivery_status'  => $item->delivery_status,
                'delivery_attempts'=> $item->delivery_attempts,
                'delivery_note'    => $item->delivery_note,
                'created_at'       => $item->created_at->diffForHumans(),
                'created_at_full'  => $item->created_at->format('d/m/Y H:i'),
            ]);

        $pendingCount = OrderItem::where('seller_id', $sellerId)
            ->where('delivery_type', 'manual')
            ->where('delivery_status', 'pending')
            ->count();

        return Inertia::render('Admin/Store/Deliveries/Index', [
            'deliveries'   => $items,
            'pendingCount' => $pendingCount,
        ]);
    }

    // ── Manual delivery ───────────────────────────────────────────────────
    public function deliver(DeliverKeyRequest $request, string $ulid): RedirectResponse
    {
        $validated = $request->validated();

        $item = OrderItem::where('ulid', $ulid)
            ->where('seller_id', $request->user()->id)
            ->whereIn('delivery_status', ['pending', 'failed'])
            ->firstOrFail();

        DB::transaction(function () use ($item, $validated, $request) {
            $key = DigitalKey::where('order_item_id', $item->id)->first();

            if (! $key) {
                $key = DigitalKey::create([
                    'ulid'            => \Illuminate\Support\Str::ulid(),
                    'product_id'      => $item->product_id,
                    'seller_id'       => $request->user()->id,
                    'key_value'       => encrypt($validated['key_value']),
                    'status'          => 'sold',
                    'order_item_id'   => $item->id,
                    'sold_at'         => now(),
                    'max_activations' => 1,
                ]);
            } else {
                $key->update([
                    'key_value' => encrypt($validated['key_value']),
                    'status'    => 'sold',
                    'sold_at'   => now(),
                ]);
            }

            $item->update([
                'delivery_status'   => 'delivered',
                'delivered_at'      => now(),
                'delivery_attempts' => $item->delivery_attempts + 1,
                'delivery_note'     => $validated['delivery_note'],
            ]);

            \App\Models\Product::where('id', $item->product_id)->increment('total_sales');

            $buyer = $item->order?->buyer;
            if ($buyer) {
                $buyer->notify(new \App\Notifications\OrderCompletedNotification($item->order));
            }

            $order        = $item->order;
            $allDelivered = $order->items()
                ->where('delivery_status', '!=', 'delivered')
                ->doesntExist();

            // [PUNTO-1] Single-Vendor: al entregar todos los items la orden se completa
            // directamente sin pasar por EscrowService ni estado 'holding'.
            if ($allDelivered && ! $order->isCompleted()) {
                $order->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);
            }
        });

        return back()->with('success', "✅ Clave entregada a {$item->order?->buyer?->name}.");
    }

    // ── Retry failed delivery ─────────────────────────────────────────────
    public function retry(Request $request, string $ulid): RedirectResponse
    {
        $item = OrderItem::where('ulid', $ulid)
            ->where('seller_id', $request->user()->id)
            ->where('delivery_status', 'failed')
            ->firstOrFail();

        $item->update(['delivery_status' => 'pending']);

        return back()->with('success', 'Item marcado como pendiente. Ahora puedes entregarlo de nuevo.');
    }
}
