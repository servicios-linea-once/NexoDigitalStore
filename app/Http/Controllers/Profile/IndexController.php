<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\TwoFactorAuth;
use App\Models\WalletTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user()->load(['wallet', 'telegramUser']);

        // ── Stats de perfil ──────────────────────────────────────────
        $stats = Cache::remember("user:{$user->id}:profile_stats", 300, function () use ($user) {
            $created = $user->created_at ?? now();
            return [
                'totalOrders'    => Order::where('buyer_id', $user->id)->count(),
                'totalSpent'     => (float) Order::where('buyer_id', $user->id)->where('status', 'completed')->sum('total'),
                'monthsMember'   => $created->diffInMonths(now()),
                'memberSince'    => $created->translatedFormat('M Y'),
            ];
        });

        $stats['ntBalance'] = $user->wallet?->balance ?? 0;
        $stats['ntLocked']  = $user->wallet?->locked_balance ?? 0;

        $activeSub = $user->activeSubscription()->with('plan')->first();

        // ── Datos de Seguridad ───────────────────────────────────────
        $hasTwoFa = TwoFactorAuth::where('user_id', $user->id)->where('is_enabled', true)->exists();

        $sessions = SessionsController::historyFor($user->id);

        $linkedTelegram = null;
        if ($user->telegramUser && $user->telegramUser->is_linked) {
            $linkedTelegram = $user->telegramUser->username;
        }

        // ── Datos de Wallet ──────────────────────────────────────────
        $wallet = $user->wallet;

        $transactions = WalletTransaction::where('wallet_id', $wallet?->id)
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // ── Datos de Pedidos ─────────────────────────────────────────
        $recentOrders = Order::where('buyer_id', $user->id)
            ->with(['items.product'])
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($o) => [
                'ulid'       => $o->ulid,
                'status'     => $o->status,
                'total'      => $o->total,
                'created_at' => $o->created_at->toIso8601String(),
                'item_count' => $o->items->count(),
                'preview'    => $o->items->first()?->product?->name,
            ]);

        $walletStats = Cache::remember("wallet:{$wallet?->id}:stats", 300, function () use ($wallet) {
            if (! $wallet) return ['incoming' => 0.0, 'spent' => 0.0];

            return [
                'incoming' => (float) WalletTransaction::where('wallet_id', $wallet->id)
                    ->whereIn('type', ['topup', 'cashback', 'refund', 'adjustment'])
                    ->sum('amount'),
                'spent'    => (float) WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', 'purchase')
                    ->sum('amount'),
            ];
        });

        return Inertia::render('Profile/Index', [
            // Perfil
            'stats'        => $stats,
            'subscription' => $activeSub ? [
                'ulid'             => $activeSub->ulid,
                'plan'             => $activeSub->plan->name,
                'plan_slug'        => $activeSub->plan->slug,
                'discount_percent' => $activeSub->discountPercent(),
                'expires_at'       => $activeSub->expires_at?->toIso8601String(),
                'days_remaining'   => $activeSub->daysRemaining(),
                'is_lifetime'      => $activeSub->expires_at === null,
            ] : null,

            // Seguridad
            'hasTwoFa'       => $hasTwoFa,
            'sessions'       => $sessions,
            'linkedGoogle'   => ! empty($user->google_id),
            'linkedSteam'    => ! empty($user->steam_id),
            'linkedTelegram' => $linkedTelegram,
            'botUsername'    => config('services.telegram.bot_username', 'NexoDigitalBot'),
            'orders'         => $recentOrders,

            // Wallet
            'wallet'         => $wallet,
            'transactions'   => $transactions,
            'walletStats'    => $walletStats,
            'filters'        => $request->only('type'),

            // Tab activo
            'activeTab'      => $request->input('tab', 'perfil'),
        ]);
    }
}
