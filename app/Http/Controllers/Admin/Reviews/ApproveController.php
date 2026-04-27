<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ApproveController extends Controller
{
    public function __invoke(int $id, ReviewService $reviews): RedirectResponse
    {
        $reviews->approve(Review::findOrFail($id));
        return back()->with('success', 'Reseña aprobada.');
    }
}
