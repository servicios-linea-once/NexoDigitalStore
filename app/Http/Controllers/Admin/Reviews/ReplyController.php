<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\ReplyReviewRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReplyController extends Controller
{
    public function __invoke(ReplyReviewRequest $request, int $id, ReviewService $reviews): RedirectResponse
    {
        // En Single-Vendor el administrador es dueño de todo, no necesitamos validar el seller_id
        $review = Review::findOrFail($id);

        try {
            $reviews->replyToReview($review, $request->validated()['reply']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Respuesta oficial publicada.');
    }
}
