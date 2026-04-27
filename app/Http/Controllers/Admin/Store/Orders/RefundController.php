<?php

namespace App\Http\Controllers\Admin\Store\Orders;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * POST /admin/store/orders/{ulid}/refund
 * Migrado desde Admin\OrderController@refund (consolidación Single-Vendor).
 */
class RefundController extends Controller
{
    public function __invoke(Request $request, string $ulid): RedirectResponse
    {
        $order = Order::where('ulid', $ulid)->firstOrFail();

        if (! $order->isCompleted()) {
            return back()->with('error', 'Solo se pueden reembolsar órdenes completadas.');
        }

        $order->update(['status' => 'refunded']);

        AuditLog::record('admin_order_refunded', $request->user()->id, $order, [
            'reason' => $request->reason ?? 'Manual refund by admin',
        ]);

        return back()->with('success', 'Orden marcada como reembolsada.');
    }
}
