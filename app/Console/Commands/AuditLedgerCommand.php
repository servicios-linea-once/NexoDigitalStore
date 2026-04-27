<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('nexo:audit-ledger {--wallet= : ULID de una billetera específica}')]
#[Description('Audita la integridad criptográfica de las billeteras y el historial de transacciones.')]
class AuditLedgerCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando auditoría de seguridad criptográfica...');

        $query = Wallet::query();
        if ($this->option('wallet')) {
            $query->where('ulid', $this->option('wallet'));
        }

        $wallets = $query->with('user')->get();
        $totalWallets = $wallets->count();
        $corruptedCount = 0;

        $bar = $this->output->createProgressBar($totalWallets);
        $bar->start();

        foreach ($wallets as $wallet) {
            $issues = [];

            // 1. Verificar firma de la billetera
            if (!$wallet->verifySignature()) {
                $issues[] = 'Firma de billetera (signature) inválida o alterada.';
            }

            // 2. Verificar cadena de transacciones
            $transactions = WalletTransaction::where('wallet_id', $wallet->id)
                ->orderBy('id', 'asc')
                ->get();

            $prevHash = null;
            $calculatedBalance = 0;

            foreach ($transactions as $tx) {
                // Verificar integridad del hash propio
                if (!$tx->verifyIntegrity()) {
                    $issues[] = "Transacción [{$tx->ulid}] tiene un hash corrupto.";
                }

                // Verificar encadenamiento
                if ($tx->previous_hash !== $prevHash) {
                    $issues[] = "Ruptura de cadena en transacción [{$tx->ulid}]. El previous_hash no coincide.";
                }

                // Verificar cálculo matemático de balance
                if (in_array($tx->type, ['deposit', 'refund', 'bonus'])) {
                    $calculatedBalance += (float) $tx->amount;
                } else {
                    $calculatedBalance -= (float) $tx->amount;
                }

                // Comparar balance registrado vs calculado
                if (abs($calculatedBalance - (float) $tx->balance_after) > 0.0001) {
                    $issues[] = "Inconsistencia de balance en transacción [{$tx->ulid}]. Registrado: {$tx->balance_after}, Calculado: {$calculatedBalance}.";
                }

                $prevHash = $tx->hash;
            }

            // 3. Comparar saldo final calculado vs saldo actual en Wallet
            if (abs($calculatedBalance - (float) $wallet->balance) > 0.0001) {
                $issues[] = "El saldo final de transacciones ({$calculatedBalance}) no coincide con el saldo de la billetera ({$wallet->balance}).";
            }

            if (!empty($issues)) {
                $corruptedCount++;
                $this->newLine();
                $this->error("❌ Billetera [{$wallet->ulid}] — Usuario: " . ($wallet->user->email ?? 'N/A') . " COMPROMETIDA");
                foreach ($issues as $issue) {
                    $this->line("   - {$issue}");
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($corruptedCount > 0) {
            $this->error("🚨 Auditoría finalizada: Se encontraron {$corruptedCount} billeteras comprometidas.");
            return Command::FAILURE;
        }

        $this->success("✅ Auditoría finalizada: Todas las billeteras (total: {$totalWallets}) e historiales están íntegros.");
        return Command::SUCCESS;
    }

    private function success($message)
    {
        $this->line("<fg=green>{$message}</>");
    }
}
