<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Presenters\ProductShowPagePresenter;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    public function __invoke(string $slug, ProductShowPagePresenter $presenter): Response
    {
        $product = Product::with([
            'category.parent',
            'images',
            'seller:id,name',
            'variants',
            'reviews' => fn ($q) => $q->with('user:id,name,avatar')->latest()->limit(10),
        ])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $related = Cache::remember("related_v2:{$product->id}:{$product->category_id}", 600, function () use ($product) {
            return Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->active()
                ->inStock()
                ->orderByDesc('rating')
                ->orderByDesc('total_sales')
                ->limit(8)
                ->get();
        });

        // Aseguramos que si la caché devolvió basura (strings o incomplete class), lo filtramos
        $related = collect($related)->filter(fn($i) => $i instanceof Product);

        return Inertia::render('Products/Show', $presenter->present($product, $related));
    }
}
