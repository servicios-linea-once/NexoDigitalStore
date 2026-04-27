<?php

namespace App\Console\Commands;

use App\Models\DigitalKey;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire-pending {--minutes=15 : Minutes before expiration}';

    protected $description = 'Expire pending orders and release reserved keys/stock/NT';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        $orders = Order::where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->with(['items', 'buyer.wallet'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No pending orders to expire.');

            return self::SUCCESS;
        }

        $expired = 0;

        foreach ($orders as $order) {
            try {
                DB::transaction(function () use ($order) {
                    // Restore stock and release keys
                    foreach ($order->items as $item) {
                        // Release the digital key back to available
                        if ($item->digitalKey) {
                            $item->digitalKey->update([
                                'status' => 'available',
                                'order_item_id' => null,
                                'sold_at' => null,
                            ]);
                        }

                        // Restore product stock
                        if ($item->product_id) {
                            $product = $item->product;
                            if ($product) {
                                $product->increment('stock_count');
                                $product->decrement('total_sales');
                            }
                        }
                    }

                    // Release locked NT if any were used
                    $ntUsed = (float) $order->nexocoins_used;
                    if ($ntUsed > 0 && $order->buyer?->wallet) {
                        $wallet = $order->buyer->wallet;
                        $wallet->decrement('locked_balance', min($ntUsed, $wallet->locked_balance));
                        $wallet->transactions()->create([
                            'user_id' => $order->buyer_id,
                            'type' => 'unlock',
                            'reason' => 'order_expired',
                            'amount' => $ntUsed,
                            'balance_after' => $wallet->fresh()->balance,
                            'note' => "NT desbloqueados: orden #{$order->ulid} expirada",
                            'reference' => "Order:{$order->id}",
                        ]);
                    }

                    // Reverse any cashback already granted
                    // (cashback should only be granted on completion, but just in case)

                    // Mark order as failed
                    $order->update([
                        'status' => 'failed',
                        'meta' => array_merge($order->meta ?? [], [
                            'expired_at' => now()->toISOString(),
                            'expiration_reason' => 'payment_timeout',
                        ]),
                    ]);
                });

                $expired++;
                Log::info("[ExpirePendingOrders] Order #{$order->ulid} expired successfully.");
            } catch (\Exception $e) {
                Log::error("[ExpirePendingOrders] Failed to expire order #{$order->ulid}: {$e->getMessage()}");
                $this->error("Failed: Order #{$order->ulid} — {$e->getMessage()}");
            }
        }

        $this->info("Expired {$expired} pending order(s).");

        return self::SUCCESS;
    }
}
