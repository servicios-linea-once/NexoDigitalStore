<?php

namespace App\Console\Commands;

use App\Models\DigitalKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredKeyReservations extends Command
{
    protected $signature   = 'keys:release-reservations {--dry-run : List expired reservations without releasing}';
    protected $description = 'Release digital keys whose payment reservation window has expired';

    public function handle(): int
    {
        $query = DigitalKey::where('status', 'reserved')
            ->where('reserved_until', '<', now());

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired key reservations found.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] {$count} reservation(s) would be released:");
            $query->with('product:id,name')->get()->each(function ($key) {
                $this->line("  → Key #{$key->id} — {$key->product->name} (reserved_until: {$key->reserved_until})");
            });
            return self::SUCCESS;
        }

        // Release reservations: set back to 'available', detach from order_item
        $released = $query->update([
            'status'         => 'available',
            'order_item_id'  => null,
            'reserved_at'    => null,
            'reserved_until' => null,
        ]);

        // Revert stock count for each affected product
        $productIds = $query->pluck('product_id')->unique();
        foreach ($productIds as $productId) {
            \App\Models\Product::where('id', $productId)->increment('stock_count');
        }

        Log::info("[ReleaseExpiredKeyReservations] Released {$released} expired key reservation(s).");
        $this->info("✅ Released {$released} expired key reservation(s).");

        return self::SUCCESS;
    }
}
