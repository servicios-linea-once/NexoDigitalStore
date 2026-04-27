<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature   = 'subscriptions:expire {--dry-run : Show expired subscriptions without deactivating}';
    protected $description = 'Mark expired user subscriptions as inactive and downgrade users to buyer role';

    public function handle(): int
    {
        $query = UserSubscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->with('user:id,name,email,role');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired subscriptions found.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("[dry-run] {$count} subscription(s) would expire:");
            $query->get()->each(fn ($sub) =>
                $this->line("  → User: {$sub->user->email} — ends_at: {$sub->ends_at}")
            );
            return self::SUCCESS;
        }

        $expired = 0;
        $query->get()->each(function (UserSubscription $sub) use (&$expired) {
            $sub->update(['status' => 'expired']);

            // If the user has no other active subscription, downgrade role
            $hasActiveOther = UserSubscription::where('user_id', $sub->user_id)
                ->where('id', '!=', $sub->id)
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->exists();

            if (! $hasActiveOther && $sub->user?->role === 'seller') {
                // Only downgrade sellers who lost their subscription, not admins
                // Optional: uncomment to force downgrade
                // $sub->user->update(['role' => 'buyer']);
            }

            Log::info("[ExpireSubscriptions] Expired subscription #{$sub->id} for user #{$sub->user_id}");
            $expired++;
        });

        $this->info("✅ Expired {$expired} subscription(s).");
        return self::SUCCESS;
    }
}
