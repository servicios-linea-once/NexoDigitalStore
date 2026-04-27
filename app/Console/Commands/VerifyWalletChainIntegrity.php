<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyWalletChainIntegrity extends Command
{
    protected $signature   = 'wallet:verify-chain {--wallet= : Specific wallet ID to verify}';
    protected $description = 'Verify the cryptographic hash chain integrity of wallet transactions';

    public function handle(): int
    {
        $query = Wallet::query();

        if ($this->option('wallet')) {
            $query->where('id', $this->option('wallet'));
        }

        $wallets     = $query->get();
        $totalFailed = 0;

        foreach ($wallets as $wallet) {
            $transactions = WalletTransaction::where('wallet_id', $wallet->id)
                ->orderBy('id')
                ->get();

            $prevHash = null;
            $failed   = [];

            foreach ($transactions as $tx) {
                // Re-generate expected hash
                $expectedHash = hash('sha256',
                    $tx->wallet_id .
                    $tx->type .
                    $tx->amount .
                    $tx->balance_after .
                    $tx->created_at->timestamp .
                    ($prevHash ?? '')
                );

                if ($tx->hash !== $expectedHash) {
                    $failed[] = $tx->id;
                }

                $prevHash = $tx->hash;
            }

            if (! empty($failed)) {
                $msg = "Wallet #{$wallet->id} COMPROMISED — invalid tx hashes: " . implode(', ', $failed);
                $this->error($msg);
                Log::critical("[WalletChainIntegrity] {$msg}");
                $totalFailed++;
            } else {
                $this->line("Wallet #{$wallet->id} ✅ — {$transactions->count()} transaction(s) verified.");
            }
        }

        if ($totalFailed === 0) {
            $this->info("✅ All wallet chains are intact.");
            return self::SUCCESS;
        }

        $this->error("⚠️  {$totalFailed} wallet(s) failed integrity check. Check logs immediately.");
        return self::FAILURE;
    }
}
