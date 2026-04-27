<?php

namespace App\Services;

use App\Jobs\RecalculateProductRating;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * ReviewService — encapsulates all review business logic.
 *
 * Extracted from ReviewController where recalculateProductRating()
 * was a private method called 5 times and business logic was scattered.
 */
class ReviewService
{
    // ── Store a new review ─────────────────────────────────────────────────

    /**
     * Create a review for a verified purchase.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \RuntimeException if already reviewed
     */
    public function storeReview(User $user, OrderItem $orderItem, array $data): Review
    {
        if (Review::where('order_item_id', $orderItem->id)->where('user_id', $user->id)->exists()) {
            throw new \RuntimeException('Ya has reseñado este producto.');
        }

        $review = Review::create([
            'product_id'    => $orderItem->product_id,
            'user_id'       => $user->id,
            'order_item_id' => $orderItem->id,
            'rating'        => $data['rating'],
            'title'         => $data['title'] ?? null,
            'body'          => $data['comment'] ?? null,
            'is_approved'   => true,
            'is_flagged'    => false,
        ]);

        // Dispatch async — does not block HTTP response
        RecalculateProductRating::dispatch($orderItem->product_id);

        return $review;
    }

    // ── Update an existing review ─────────────────────────────────────────

    public function updateReview(Review $review, array $data): Review
    {
        $review->update([
            'rating' => $data['rating'],
            'body'   => $data['comment'] ?? $review->body,
            'title'  => $data['title'] ?? $review->title,
        ]);

        RecalculateProductRating::dispatch($review->product_id);

        return $review->fresh();
    }

    // ── Seller reply ──────────────────────────────────────────────────────

    /**
     * @throws \RuntimeException if review already has a seller reply
     */
    public function replyToReview(Review $review, string $reply): void
    {
        if ($review->seller_reply !== null) {
            throw new \RuntimeException('Ya respondiste a esta reseña.');
        }

        $review->update([
            'seller_reply'      => $reply,
            'seller_replied_at' => now(),
        ]);
    }

    // ── Helpfulness vote ──────────────────────────────────────────────────

    /**
     * Toggle or change a helpfulness vote.
     * Returns updated counts: ['helpful' => int, 'not_helpful' => int]
     */
    public function voteReview(User $user, Review $review, string $vote): array
    {
        $existing = ReviewVote::where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->vote === $vote
                ? $existing->delete()          // toggle off
                : $existing->update(['vote' => $vote]); // change vote
        } else {
            ReviewVote::create([
                'review_id' => $review->id,
                'user_id'   => $user->id,
                'vote'      => $vote,
            ]);
        }

        $counts = ReviewVote::where('review_id', $review->id)
            ->selectRaw("SUM(vote='helpful') as helpful, SUM(vote='not_helpful') as not_helpful")
            ->first();

        $helpful    = (int) ($counts->helpful ?? 0);
        $notHelpful = (int) ($counts->not_helpful ?? 0);

        $review->update([
            'helpful_count'     => $helpful,
            'not_helpful_count' => $notHelpful,
        ]);

        return ['helpful' => $helpful, 'not_helpful' => $notHelpful];
    }

    // ── Admin moderation ──────────────────────────────────────────────────

    public function approve(Review $review): void
    {
        $review->update(['is_approved' => true, 'approved_at' => now(), 'is_flagged' => false]);
        RecalculateProductRating::dispatch($review->product_id);

    }

    public function reject(Review $review): void
    {
        $review->update(['is_approved' => false]);
        RecalculateProductRating::dispatch($review->product_id);

    }

    public function flag(Review $review, string $reason): void
    {
        $review->update(['is_flagged' => true, 'flag_reason' => $reason]);
    }

    public function delete(Review $review): void
    {
        $productId = $review->product_id;
        $review->delete();
        RecalculateProductRating::dispatch($productId);

    }

    /**
     * Synchronous fallback — used directly only in tests/seeders.
     * In production, prefer dispatching RecalculateProductRating job.
     */
    public function recalculateProductRating(int $productId): void
    {
        $stats = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        Product::where('id', $productId)->update([
            'rating'       => round((float) ($stats->avg_rating ?? 0), 2),
            'rating_count' => (int) ($stats->total ?? 0),
        ]);

        // Bust caches
        Cache::forget('home:payload');
        Cache::forget("related:{$productId}:*");
    }
}
