<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // GET /api/v1/wishlist
    public function index(Request $request): JsonResponse
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with(['product' => fn ($q) => $q->with(['coverImage', 'promotions'])])
            ->latest()
            ->get()
            ->map(fn ($w) => [
                'id'                   => $w->id,
                'product_id'           => $w->product->id,
                'ulid'                 => $w->product->ulid,
                'name'                 => $w->product->name,
                'slug'                 => $w->product->slug,
                'platform'             => $w->product->platform,
                'region'               => $w->product->region,
                'price_usd'            => (float) $w->product->price_usd,
                'discounted_price_usd' => $w->product->discounted_price_usd,
                'discount_percent'     => $w->product->discount_percent ?? 0,
                'stock_count'          => (int) $w->product->stock_count,
                'cover_image'          => $w->product->coverImage?->url,
                'rating'               => round((float) $w->product->rating, 1),
                'added_at'             => $w->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $items]);
    }

    // POST /api/v1/wishlist/toggle
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $existing = Wishlist::where('user_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['in_wishlist' => false]);
        }

        Wishlist::create([
            'user_id'    => $request->user()->id,
            'product_id' => $request->product_id,
        ]);

        return response()->json(['in_wishlist' => true]);
    }

    // DELETE /api/v1/wishlist/clear
    public function clear(Request $request): JsonResponse
    {
        Wishlist::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Wishlist cleared.']);
    }
}
