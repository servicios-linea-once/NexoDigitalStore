<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use App\Models\DigitalKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $sellerId = $request->user()->id;

        // ── Sales stats — consolidated into ONE query ─────────────────────
        $salesStats = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn ($q) => $q->where('status', 'completed'))
            ->selectRaw("
                COALESCE(SUM(total_price), 0)                                              AS total_earnings,
                COALESCE(SUM(seller_earnings), 0)                                          AS total_seller_earnings,
                COALESCE(SUM(CASE WHEN DATE(order_items.created_at) = CURDATE() THEN total_price ELSE 0 END), 0) AS today_sales,
                COALESCE(SUM(CASE WHEN order_items.created_at >= DATE(NOW() - INTERVAL WEEKDAY(NOW()) DAY) THEN total_price ELSE 0 END), 0) AS week_sales,
                COALESCE(SUM(CASE WHEN order_items.created_at >= DATE_FORMAT(NOW(),'%Y-%m-01') THEN total_price ELSE 0 END), 0) AS month_sales,
                COUNT(CASE WHEN order_items.created_at >= DATE_FORMAT(NOW(),'%Y-%m-01') THEN 1 END)              AS month_orders
            ")
            ->first();

        // ── Key inventory stats ──────────────────────────────────────────
        $keyRow = DB::table('digital_keys')
            ->where('seller_id', $sellerId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'available') as available,
                SUM(status = 'sold') as sold,
                SUM(status = 'reserved') as reserved
            ")
            ->first();
        $keyStats = [
            'total'     => (int) ($keyRow->total ?? 0),
            'available' => (int) ($keyRow->available ?? 0),
            'sold'      => (int) ($keyRow->sold ?? 0),
            'reserved'  => (int) ($keyRow->reserved ?? 0),
        ];

        // ── Product counts consolidated ──────────────────────────────────
        $productRow = DB::table('products')
            ->where('seller_id', $sellerId)
            ->whereNull('deleted_at')
            ->selectRaw("COUNT(*) as total, SUM(status = 'active') as active")
            ->first();
        $productCounts = [
            'total'  => (int) ($productRow->total ?? 0),
            'active' => (int) ($productRow->active ?? 0),
        ];

        // ── Low stock products (< 5 keys) ────────────────────────────────
        $lowStockProducts = Product::where('seller_id', $sellerId)
            ->where('status', 'active')
            ->where('stock_count', '<', config('nexo.inventory.low_stock_threshold', 5))
            ->orderBy('stock_count')
            ->limit(10)
            ->get(['id', 'ulid', 'name', 'slug', 'stock_count', 'platform']);

        // ── Top selling products ─────────────────────────────────────────
        $topProducts = Product::where('seller_id', $sellerId)
            ->where('total_sales', '>', 0)
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get(['id', 'ulid', 'name', 'total_sales', 'rating', 'stock_count', 'price_usd']);

        // ── Sales chart (last 30 days) ───────────────────────────────────
        $salesChart = OrderItem::where('seller_id', $sellerId)
            ->whereHas('order', fn ($q) => $q->where('status', 'completed'))
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as revenue, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date'    => $row->date,
                'revenue' => round((float) $row->revenue, 2),
                'count'   => (int) $row->count,
            ]);

        // ── Recent sales ─────────────────────────────────────────────────
        $recentSales = OrderItem::where('seller_id', $sellerId)
            ->with(['order:id,ulid,status,created_at,buyer_id', 'order.buyer:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'id'              => $item->id,
                'order_ulid'      => $item->order?->ulid,
                'order_status'    => $item->order?->status,
                'buyer_name'      => $item->order?->buyer?->name ?? 'N/A',
                'product_name'    => $item->product_name,
                'total_price'     => (float) $item->total_price,
                'seller_earnings' => (float) $item->seller_earnings,
                'delivery_status' => $item->delivery_status,
                'created_at'      => $item->created_at->diffForHumans(),
            ]);

        return Inertia::render('Admin/Store/Dashboard', [
            'stats'  => [
                'active_products'   => $productCounts['active'],
                'total_products'    => $productCounts['total'],
                'available_keys'    => $keyStats['available'],
                'total_keys'        => $keyStats['total'],
                'sales_this_month'  => round((float) ($salesStats->month_sales ?? 0), 2),
                'orders_this_month' => (int) ($salesStats->month_orders ?? 0),
                'balance'           => round((float) ($request->user()->wallet?->balance ?? 0), 2),
            ],
            'lowStockProducts' => $lowStockProducts,
            'topProducts'      => $topProducts->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'total_sales' => $p->total_sales,
                'stock_count' => $p->stock_count,
                'price_usd'   => (float) $p->price_usd,
            ]),
            'salesChart'  => $salesChart,
            'recentSales' => $recentSales,
        ]);
    }
}
