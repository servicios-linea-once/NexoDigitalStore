<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reviews\FlagReviewRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class FlagController extends Controller
{
    public function __invoke(FlagReviewRequest $request, int $id, ReviewService $reviews): RedirectResponse
    {
        $reviews->flag(Review::findOrFail($id), $request->validated()['reason']);
        return back()->with('success', 'Reseña marcada.');
    }
}
