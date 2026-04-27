<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WalletService — centralizes all wallet credit/debit operations.
 *
 * Eliminates the repeated pattern across EscrowService and CheckoutController:
 *   lockForUpdate → assertNotCompromised → increment/decrement → create transaction
 *
 * All operations use pessimistic locking to prevent race conditions.
 */
class WalletService
{
    /**
     * Credit an amount to a wallet and record the transaction.
     * Must be called inside a DB::transaction().
     */
    public function credit(
        Wallet $wallet,
        float  $amount,
        string $reason,
        string $note,
        string $reference
    ): WalletTransaction {
        $wallet->assertNotCompromised();
        $wallet->increment('balance', $amount);

        $fresh = $wallet->fresh();

        $tx = WalletTransaction::create([
            'wallet_id'     => $wallet->id,
            'user_id'       => $wallet->user_id,
            'type'          => 'credit',
            'reason'        => $reason,
            'amount'        => $amount,
            'balance_after' => $fresh->balance,
            'note'          => $note,
            'reference'     => $reference,
        ]);

        Log::info("[WalletService::credit] +{$amount} NT to wallet #{$wallet->id} ({$reason})");

        return $tx;
    }

    /**
     * Debit an amount from a wallet and record the transaction.
     * Must be called inside a DB::transaction().
     *
     * @throws \RuntimeException if insufficient balance
     */
    public function debit(
        Wallet $wallet,
        float  $amount,
        string $reason,
        string $note,
        string $reference
    ): WalletTransaction {
        if ($wallet->balance < $amount) {
            throw new \RuntimeException("Saldo insuficiente: se requieren {$amount} NT, disponible {$wallet->balance} NT.");
        }

        $wallet->assertNotCompromised();
        $wallet->decrement('balance', $amount);

        $fresh = $wallet->fresh();

        $tx = WalletTransaction::create([
            'wallet_id'     => $wallet->id,
            'user_id'       => $wallet->user_id,
            'type'          => 'debit',
            'reason'        => $reason,
            'amount'        => -$amount,
            'balance_after' => $fresh->balance,
            'note'          => $note,
            'reference'     => $reference,
        ]);

        Log::info("[WalletService::debit] -{$amount} NT from wallet #{$wallet->id} ({$reason})");

        return $tx;
    }

    /**
     * Lock a wallet for pessimistic write operations.
     * Returns a fresh locked instance — must be called inside DB::transaction().
     */
    public function lockForWrite(int $walletId): Wallet
    {
        return Wallet::where('id', $walletId)->lockForUpdate()->firstOrFail();
    }

    /**
     * Ensure a user has a wallet, creating one if needed.
     */
    public function ensureWallet(\App\Models\User $user): Wallet
    {
        if (! $user->wallet) {
            $wallet = Wallet::create(['user_id' => $user->id]);
            $user->setRelation('wallet', $wallet);
            return $wallet;
        }

        return $user->wallet;
    }
}
