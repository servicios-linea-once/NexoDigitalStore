<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');
        $wallet = $user->wallet;

        if (! $wallet) {
            return response()->json(['balance' => 0, 'locked_balance' => 0, 'transactions' => []]);
        }

        return response()->json([
            'balance' => (float) $wallet->balance,
            'locked_balance' => (float) $wallet->locked_balance,
            'available' => (float) ($wallet->balance - $wallet->locked_balance),
            'rate_usd' => (float) config('nexo.token.rate_to_usd', 0.10),
            'balance_usd' => round($wallet->balance * config('nexo.token.rate_to_usd', 0.10), 2),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = $request->user()
            ->wallet
            ?->transactions()
            ->latest()
            ->paginate(20);

        if (! $transactions) {
            return response()->json(['data' => [], 'meta' => ['total' => 0]]);
        }

        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'total' => $transactions->total(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }
}
