<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $days = (int) $request->get('period', 30);
        $days = in_array($days, [7, 30, 90]) ? $days : 30;
        $from = now()->subDays($days)->startOfDay();
        $to   = now()->endOfDay();

        // ── SECCIÓN 1: KPIs del sistema (Base) ──────────────────────────────────
        $totalUsers    = User::count();
        $newUsers      = User::where('created_at', '>=', $from)->count();
        $totalOrders   = Order::where('created_at', '>=', $from)->count();
        $revenue       = (float) Order::where('status', 'completed')->where('created_at', '>=', $from)->sum('total');
        $activeProducts = Product::where('status', 'active')->count();

        // Trends vs período anterior
        $prevFrom     = now()->subDays($days * 2)->startOfDay();
        $prevTo       = now()->subDays($days)->endOfDay();
        $prevRevenue  = (float) Order::where('status', 'completed')->whereBetween('created_at', [$prevFrom, $prevTo])->sum('total');
        $prevOrders   = Order::whereBetween('created_at', [$prevFrom, $prevTo])->count();
        $revenuePct   = $prevRevenue > 0 ? round(($revenue - $prevRevenue) / $prevRevenue * 100, 1) : 0;
        $ordersPct    = $prevOrders  > 0 ? round(($totalOrders - $prevOrders) / $prevOrders * 100, 1) : 0;

        // ── SECCIÓN 2: Inventario ──────────────────────────────────────────
        $keyRow = DB::table('digital_keys')
            ->selectRaw("COUNT(*) as total, SUM(status='available') as available, SUM(status='sold') as sold")
            ->first();

        $productCounts = DB::table('products')
            ->whereNull('deleted_at')
            ->selectRaw("COUNT(*) as total, SUM(status='active') as active, SUM(stock_count < 5 AND status='active') as low_stock")
            ->first();

        // ── SECCIÓN 3: KPIs Financieros (Ex-Earnings) ──────────────────────────
        $ntRateUsd = (float) StoreSetting::get('nt_rate_to_usd', 0.10);
        
        $cacheKey = sprintf('admin:dashboard:financials:%s:%s', $from->format('Ymd'), $to->format('Ymd'));
        $financials = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($from, $to, $ntRateUsd) {
            $base = fn () => DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->where('order_items.delivery_status', 'delivered')
                ->whereBetween('order_items.created_at', [$from, $to]);

            $totalRevenue = (float) $base()->sum('order_items.total_price');
            $totalCashbackGranted = (float) $base()->sum('order_items.cashback_amount');
            $cashbackUsdEquiv = round($totalCashbackGranted * $ntRateUsd, 4);
            $netRevenue = max(0, $totalRevenue - $cashbackUsdEquiv);

            return [
                'gross' => $totalRevenue,
                'cashback' => $totalCashbackGranted,
                'cashback_usd' => $cashbackUsdEquiv,
                'net' => $netRevenue,
            ];
        });

        // ── SECCIÓN 4: Revenue Chart (Merged) ──────────────────────────────
        $chartCacheKey = sprintf('admin:dashboard:chart:%s:%s', $from->format('Ymd'), $to->format('Ymd'));
        $chartData = Cache::remember($chartCacheKey, now()->addMinutes(15), function () use ($from, $to) {
            return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->where('order_items.delivery_status', 'delivered')
                ->whereBetween('order_items.created_at', [$from, $to])
                ->select(
                    DB::raw('DATE(order_items.created_at) as date'),
                    DB::raw('SUM(order_items.total_price) as revenue'),
                    DB::raw('SUM(order_items.cashback_amount) as cashback')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn ($r) => [
                    'date' => Carbon::parse($r->date)->format('d/m'),
                    'revenue' => (float) $r->revenue,
                    'cashback' => (float) $r->cashback,
                ]);
        });

        // ── SECCIÓN 5: Listas Recientes ───────────────────────────────────
        $recentOrders = Order::with(['buyer:id,name,email', 'items'])
            ->latest()
            ->limit(10)
            ->get();

        $topProducts = Product::where('total_sales', '>', 0)
            ->orderByDesc('total_sales')
            ->limit(6)
            ->get(['id', 'ulid', 'name', 'total_sales', 'stock_count', 'price_usd', 'status'])
            ->map(fn ($p) => [
                'id'          => $p->id,
                'ulid'        => $p->ulid,
                'name'        => $p->name,
                'sold_count'  => (int)   $p->total_sales,
                'stock_count' => (int)   $p->stock_count,
                'price_usd'   => (float) $p->price_usd,
            ]);

        $roleStats = User::select('role', DB::raw('COUNT(*) as count'))
            ->whereIn('role', ['admin', 'seller', 'buyer'])
            ->groupBy('role')
            ->get()
            ->map(function ($r) use ($totalUsers) {
                return [
                    'role'  => $r->role,
                    'count' => $r->count,
                    'pct'   => $totalUsers > 0 ? round($r->count / $totalUsers * 100) : 0,
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users'     => $totalUsers,
                'new_users'       => $newUsers,
                'total_orders'    => $totalOrders,
                'revenue'         => round($revenue, 2),
                'revenue_pct'     => $revenuePct,
                'orders_pct'      => $ordersPct,
                'active_products' => $activeProducts,
                'available_keys'  => (int) ($keyRow->available ?? 0),
                'low_stock'       => (int) ($productCounts->low_stock ?? 0),
                // Financieros
                'financials'      => $financials,
            ],
            'revenueChart' => $chartData,
            'recentOrders' => $recentOrders,
            'topProducts'  => $topProducts,
            'roleStats'    => $roleStats,
            'period'       => $days,
        ]);
    }
}
