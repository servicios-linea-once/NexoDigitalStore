<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class RejectController extends Controller
{
    public function __invoke(int $id, ReviewService $reviews): RedirectResponse
    {
        $reviews->reject(Review::findOrFail($id));
        return back()->with('success', 'Reseña ocultada.');
    }
}
