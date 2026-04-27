<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recalculates the average rating and review count for a product.
 * Dispatched from ReviewService after any review lifecycle event (store, vote, admin approve/reject).
 * Runs on the 'default' queue to avoid blocking the HTTP request.
 */
class RecalculateProductRating implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    public function __construct(public readonly int $productId) {}

    public function handle(): void
    {
        $result = DB::table('reviews')
            ->where('product_id', $this->productId)
            ->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        if (! $result) {
            return;
        }

        Product::where('id', $this->productId)->update([
            'rating'       => round((float) $result->avg_rating, 2),
            'rating_count' => (int) $result->total,
        ]);

        // Bust caches that may contain this product
        Cache::forget("related:{$this->productId}:" . (Product::find($this->productId)?->category_id ?? 0));
        Cache::forget('home:payload');

        Log::debug("RecalculateProductRating done", ['product_id' => $this->productId]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("RecalculateProductRating failed", [
            'product_id' => $this->productId,
            'error'      => $e->getMessage(),
        ]);
    }
}
