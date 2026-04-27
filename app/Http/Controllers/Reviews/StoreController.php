<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\StoreReviewRequest;
use App\Models\OrderItem;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class StoreController extends Controller
{
    public function __invoke(StoreReviewRequest $request, ReviewService $reviews): RedirectResponse
    {
        $user = $request->user();

        $orderItem = OrderItem::where('id', $request->order_item_id)
            ->whereHas('order', fn ($q) => $q
                ->where('buyer_id', $user->id)
                ->where('status', 'completed')
            )
            ->firstOrFail();

        try {
            $reviews->storeReview($user, $orderItem, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', '¡Reseña publicada! Gracias por tu opinión. ⭐');
    }
}
