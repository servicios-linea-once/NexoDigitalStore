<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [PUNTO-1] DEPRECADO — Single-Vendor (Nexo eStore)
 *
 * Este comando existía para liberar fondos retenidos por EscrowService al cabo
 * de 24h. Con el modelo Single-Vendor los pagos completan directamente sin
 * pasar por estado 'holding', por lo que este comando ya no tiene efecto.
 *
 * Se mantiene como no-op para evitar errores en schedulers legados que
 * aún lo invoquen. Eliminarlo de routes/console.php en la próxima release.
 */
class EscrowAutoRelease extends Command
{
    protected $signature   = 'escrow:auto-release {--dry-run : Preview (no-op en single-vendor)}';
    protected $description = '[DEPRECATED] No-op en modelo Single-Vendor. EscrowService ha sido eliminado.';

    public function handle(): int
    {
        $this->warn('[EscrowAutoRelease] Este comando está DEPRECADO en el modelo Single-Vendor.');
        $this->warn('Los pagos se completan directamente. No hay fondos en estado "holding".');
        Log::info('[EscrowAutoRelease] Invocado pero no-op (Single-Vendor mode).');

        return self::SUCCESS;
    }
}
