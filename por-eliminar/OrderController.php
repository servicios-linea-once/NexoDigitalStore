<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::with(['buyer:id,name,email', 'items'])
            ->when($request->search, fn ($q, $s) => $q->where('ulid', 'like', "%{$s}%")
                ->orWhereHas('buyer', fn ($u) => $u->where('email', 'like', "%{$s}%"))
            )
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->method, fn ($q, $m) => $q->where('payment_method', $m))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'method']),
        ]);
    }

    public function show(string $ulid): Response
    {
        $order = Order::with([
            'buyer:id,name,email',
            'items.product:id,ulid,name',
            'payments',
            'disputes',
        ])->where('ulid', $ulid)->firstOrFail();

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function refund(Request $request, string $ulid): RedirectResponse
    {
        $order = Order::where('ulid', $ulid)->firstOrFail();

        if (! $order->isCompleted()) {
            return back()->with('error', 'Solo se pueden reembolsar órdenes completadas.');
        }

        $order->update(['status' => 'refunded']);

        AuditLog::record('admin_order_refunded', $request->user()->id, [
            'order_ulid' => $order->ulid,
            'total' => $order->total,
            'reason' => $request->reason ?? 'Manual refund by admin',
        ]);

        return back()->with('success', 'Orden marcada como reembolsada.');
    }
}
