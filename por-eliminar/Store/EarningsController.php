<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * EarningsController — Ingresos de Nexo eStore (Single-Vendor)
 *
 * Métricas contables de la tienda. No existen comisiones externas ni SellerProfile.
 * El 100% del precio de venta (total_price) es ingreso bruto de la tienda.
 * El único "costo variable" es el cashback en NexoTokens otorgado al comprador.
 *
 * Modelo financiero:
 *   total_revenue         = SUM(order_items.total_price)  [ingreso bruto]
 *   total_cashback_granted = SUM(order_items.cashback_amount) [costo NT]
 *   net_revenue           = total_revenue - total_cashback_granted
 *
 * Rendimiento: Cache::remember de 30 min con clave parametrizada por fechas.
 */
class EarningsController extends Controller
{
    public function index(Request $request): Response
    {
        // ── Parámetros de filtro ───────────────────────────────────────────
        $days  = (int)    $request->input('period', 30);
        $days  = in_array($days, [7, 30, 60, 90]) ? $days : 30; // whitelist

        $from  = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->subDays($days)->startOfDay();

        $to    = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        // nt_rate_to_usd necesaria fuera de caché para el cálculo de net en transacciones
        $ntRateUsd = (float) StoreSetting::get('nt_rate_to_usd', 0.10);

        // ── Clave de caché parametrizada ──────────────────────────────────
        // Sufijo de fecha para invalidar si cambia el rango — no comparte caché
        // entre distintas solicitudes de período.
        $cacheKey = sprintf(
            'earnings:kpi:%s:%s',
            $from->format('Ymd'),
            $to->format('Ymd')
        );

        // ── KPIs principales (cacheados 30 min) ───────────────────────────
        $kpis = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($from, $to) {

            // Base query reutilizable: order_items entregados en órdenes completadas
            $base = fn () => DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->where('order_items.delivery_status', 'delivered')
                ->whereBetween('order_items.created_at', [$from, $to]);

            // Ingresos brutos: sum(total_price) — el 100% de cada venta
            $totalRevenue = (float) $base()->sum('order_items.total_price');

            // Cashback otorgado en NexoTokens (único costo variable de la tienda)
            $totalCashbackGranted = (float) $base()->sum('order_items.cashback_amount');

            // Items vendidos y entregados
            $totalSalesCount = (int) $base()->count();

            // Ingreso neto = bruto - cashback (valor económico del NT al precio de conversión)
            $cashbackRateUsd = (float) StoreSetting::get('nt_rate_to_usd', 0.10);
            $cashbackUsdEquiv = round($totalCashbackGranted * $cashbackRateUsd, 4);
            $netRevenue       = max(0, $totalRevenue - $cashbackUsdEquiv);

            // Órdenes completadas en el período
            $ordersCount = (int) DB::table('orders')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$from, $to])
                ->count();

            // Ticket promedio
            $avgOrderValue = $ordersCount > 0
                ? round($totalRevenue / $ordersCount, 2)
                : 0.0;

            // Cashback rate operativo de la tienda (para mostrar en UI)
            $cashbackRate = (float) StoreSetting::get('default_cashback_rate', 5.0);

            return compact(
                'totalRevenue',
                'totalCashbackGranted',
                'totalSalesCount',
                'netRevenue',
                'cashbackUsdEquiv',
                'ordersCount',
                'avgOrderValue',
                'cashbackRate'
            );
        });

        // ── Gráfico diario (últimos N días) — cacheado 30 min ─────────────
        $chartCacheKey = sprintf('earnings:chart:%s:%s', $from->format('Ymd'), $to->format('Ymd'));

        $dailyData = Cache::remember($chartCacheKey, now()->addMinutes(30), function () use ($from, $to) {
            return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', 'completed')
                ->where('order_items.delivery_status', 'delivered')
                ->whereBetween('order_items.created_at', [$from, $to])
                ->select(
                    DB::raw('DATE(order_items.created_at) as date'),
                    DB::raw('SUM(order_items.total_price)     as revenue'),
                    DB::raw('SUM(order_items.cashback_amount) as cashback'),
                    DB::raw('COUNT(*)                          as sales')
                )
                ->groupBy(DB::raw('DATE(order_items.created_at)'))
                ->orderBy('date')
                ->get();
        });

        // ── Transacciones recientes (no cacheadas — datos en vivo) ─────────
        $transactions = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('users',  'orders.buyer_id',      '=', 'users.id')
            ->where('orders.status', 'completed')
            ->where('order_items.delivery_status', 'delivered')
            ->whereBetween('order_items.created_at', [$from, $to])
            ->select(
                'orders.ulid       as order_ulid',
                'order_items.ulid  as item_ulid',
                'order_items.product_name',
                'order_items.quantity',
                DB::raw('order_items.total_price                                       as revenue'),
                DB::raw('order_items.cashback_amount                                   as cashback'),
                DB::raw("(order_items.total_price - order_items.cashback_amount * {$ntRateUsd}) as net"),
                'order_items.delivery_status',
                'order_items.created_at as date',
                'users.name             as buyer_name',
                'users.email            as buyer_email'
            )
            ->latest('order_items.created_at')
            ->limit(25)
            ->get()
            ->map(fn ($row) => [
                'order_ulid'      => $row->order_ulid,
                'item_ulid'       => $row->item_ulid,
                'product_name'    => $row->product_name,
                'quantity'        => (int)   $row->quantity,
                'revenue'         => (float) $row->revenue,
                'cashback'        => (float) $row->cashback,
                'net'             => (float) $row->net,
                'delivery_status' => $row->delivery_status,
                'date'            => $row->date,
                'buyer_name'      => $row->buyer_name,
                'buyer_email'     => $row->buyer_email,
            ]);

        // ── Formateo del gráfico ───────────────────────────────────────────
        $chartData = [
            'labels'   => $dailyData->pluck('date')
                ->map(fn ($d) => Carbon::parse($d)->format('d/m'))
                ->toArray(),
            'revenue'  => $dailyData->pluck('revenue')
                ->map(fn ($v) => (float) $v)
                ->toArray(),
            'cashback' => $dailyData->pluck('cashback')
                ->map(fn ($v) => (float) $v)
                ->toArray(),
            'sales'    => $dailyData->pluck('sales')
                ->map(fn ($v) => (int) $v)
                ->toArray(),
        ];

        // ── Render Inertia ─────────────────────────────────────────────────
        return Inertia::render('Admin/Store/Earnings', [
            // ── KPIs contables principales
            'earnings' => [
                'total_revenue'          => $kpis['totalRevenue'],
                'total_cashback_granted' => $kpis['totalCashbackGranted'],
                'cashback_usd_equiv'     => $kpis['cashbackUsdEquiv'],
                'net_revenue'            => $kpis['netRevenue'],
                'total_sales_count'      => $kpis['totalSalesCount'],
                'orders_count'           => $kpis['ordersCount'],
                'avg_order_value'        => $kpis['avgOrderValue'],
                'cashback_rate'          => $kpis['cashbackRate'],
            ],

            // ── Gráfico de ingresos diarios (últimos N días)
            'chartData' => $chartData,

            // ── Tabla de transacciones recientes (últimas 25)
            'transactions' => $transactions,

            // ── Filtros activos (devueltos para que el frontend pueda rellenar los inputs)
            'filters' => [
                'period' => $days,
                'from'   => $from->toDateString(),
                'to'     => $to->toDateString(),
            ],
        ]);
    }
}
