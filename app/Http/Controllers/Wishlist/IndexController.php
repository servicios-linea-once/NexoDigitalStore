<?php

namespace App\Http\Controllers\Wishlist;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // OPTIMIZACIÓN: Se eliminó el eager loading de 'coverImage' y 'promotions'
        // porque el modelo Product ya los trae por defecto. Esto evita duplicidad de carga.
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with(['product.category:id,name,slug'])
            ->latest()
            ->get()
            ->map(fn ($w) => [
                'id'                   => $w->id,
                'product_id'           => $w->product_id,
                'name'                 => $w->product->name,
                'slug'                 => $w->product->slug,
                'platform'             => $w->product->platform,
                'region'               => $w->product->region,
                'price_usd'            => (float) $w->product->price_usd,
                'discounted_price_usd' => $w->product->discounted_price_usd,
                'discount_percent'     => $w->product->active_promotion?->discount_value ?? 0,
                'cashback_percent'     => (float) $w->product->cashback_percent,
                'stock_count'          => (int) $w->product->stock_count,
                'is_featured'          => (bool) $w->product->is_featured,
                'rating'               => round((float) $w->product->rating, 1),
                'rating_count'         => (int) $w->product->rating_count,
                'cover_image'          => $w->product->coverImage?->url,
                'category'             => $w->product->category?->name,
                'added_at'             => $w->created_at->diffForHumans(),
                'in_stock'             => $w->product->stock_count > 0,
                'ulid'                 => $w->product->ulid,
            ]);

        return Inertia::render('Wishlist/Index', [
            'items' => $items,
        ]);
    }
}
