<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Presenters\HomePagePresenter;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(HomePagePresenter $presenter): Response
    {
        $categories = Cache::remember('home:categories', 3600, function () {
            return Category::where('is_active', true)
                ->whereNull('parent_id')
                ->where('is_featured', true)
                ->get(['id', 'name', 'slug', 'icon', 'color', 'image']);
        });

        $productsPayload = Cache::remember('home:products', 300, function () use ($presenter, $categories) {
            // Usar scopes reutilizables para queries comunes
            $featured = Product::featuredActive()
                ->byRating()
                ->limit(8)
                ->get();

            $newArrivals = Product::active()
                ->inStock()
                ->newest()
                ->limit(8)
                ->get();

            $bestSellers = Product::active()
                ->inStock()
                ->bestSellers()
                ->limit(8)
                ->get();

            return $presenter->present($featured, $newArrivals, $bestSellers, $categories);
        });

        $safeProductsPayload = $productsPayload ?? [
            'featured'    => [],
            'newArrivals' => [],
            'bestSellers' => [],
            'categories' => [],
        ];

        return Inertia::render('Home/Index', $safeProductsPayload);
    }
}
