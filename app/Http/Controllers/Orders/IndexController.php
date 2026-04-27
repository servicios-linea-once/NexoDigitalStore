<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // OPTIMIZACIÓN: Usamos withCount('items') en lugar de cargar todos los modelos
        // en memoria solo para saber la cantidad. Esto reduce drásticamente el consumo de RAM.
        $orders = Order::where('buyer_id', $request->user()->id)
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return Inertia::render('Orders/Index', [
            'orders' => $orders->through(fn ($o) => [
                'id'             => $o->id,
                'ulid'           => $o->ulid,
                'status'         => $o->status,
                'total'          => (float) $o->total,
                'currency'       => $o->currency,
                'payment_method' => $o->payment_method,
                'items_count'    => $o->items_count, // Usamos la propiedad generada por withCount
                'completed_at'   => $o->completed_at?->format('d/m/Y H:i'),
                'created_at'     => $o->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }
}
