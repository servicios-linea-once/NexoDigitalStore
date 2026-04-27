<?php

namespace App\Http\Controllers\Admin\Store\Orders;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $orders = QueryBuilder::for(Order::class)
            ->with(['buyer:id,name,email', 'items'])
            ->allowedFilters([
                'status',
                AllowedFilter::callback('global', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('ulid', 'like', "%{$value}%")
                          ->orWhereHas('buyer', fn ($u) => $u->where('name', 'like', "%{$value}%"));
                    });
                })
            ])
            ->allowedSorts(['id', 'ulid', 'total', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'    => Order::count(),
            'pending'  => Order::where('status', 'pending')->count(),
            'earnings' => (float) OrderItem::sum('seller_earnings'),
        ];

        return Inertia::render('Admin/Store/Orders/Index', [
            'orders'  => $orders->through(fn ($o) => [
                'id'          => $o->id,
                'ulid'        => $o->ulid,
                'buyer'       => $o->buyer ? ['name' => $o->buyer->name, 'email' => $o->buyer->email] : null,
                'status'      => $o->status,
                'total'       => (float) $o->total,
                'currency'    => $o->currency,
                'created_at'  => $o->created_at->toIso8601String(),
                'paid_at'     => $o->paid_at?->toIso8601String(),
                'items_count' => $o->items->count(),
                'items'       => $o->items->map(fn($i) => ['id' => $i->id, 'product_name' => $i->product_name, 'seller_earnings' => (float)$i->seller_earnings]),
                'earnings'    => (float) $o->items->sum('seller_earnings'),
            ]),
            'stats'   => $stats,
            'filters' => $request->only(['filter', 'sort']),
        ]);
    }
}
