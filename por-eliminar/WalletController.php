<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user   = $request->user();
        $wallet = $user->wallet;

        $transactions = WalletTransaction::where('wallet_id', $wallet?->id)
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Opt: Cacheamos los gastos y recargas por 5 minutos (limpiado si hay nueva recarga)
        $stats = Cache::remember("wallet:{$wallet?->id}:stats", 300, function () use ($wallet) {
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

        return Inertia::render('Profile/Wallet', [
            'wallet'       => $wallet,
            'transactions' => $transactions,
            'stats'        => $stats,
            'filters'      => $request->only('type'),
        ]);
    }
}
