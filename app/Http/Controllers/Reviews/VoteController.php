<?php

namespace App\Http\Controllers\Reviews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\VoteReviewRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class VoteController extends Controller
{
    public function __invoke(VoteReviewRequest $request, int $id, ReviewService $reviews): JsonResponse
    {
        $review = Review::findOrFail($id);
        $counts = $reviews->voteReview($request->user(), $review, $request->validated()['vote']);

        return response()->json($counts);
    }
}
