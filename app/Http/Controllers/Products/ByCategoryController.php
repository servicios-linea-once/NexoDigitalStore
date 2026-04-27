<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Presenters\ProductIndexPagePresenter;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ByCategoryController extends Controller
{
    public function __invoke(string $slug, ProductIndexPagePresenter $presenter): Response
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $ids = Category::where('parent_id', $category->id)->pluck('id')->push($category->id);

        $products = Product::whereIn('category_id', $ids)
            ->active()
            ->latest()
            ->paginate(24);

        return Inertia::render('Products/Index', $presenter->present(
            $products,
            $this->parentCategoriesForFilter(),
            ['category' => $slug],
            $category->name,
        ));
    }

    private function parentCategoriesForFilter(): array
    {
        return Cache::remember('nav_categories', 3600, function () {
            return Category::where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'icon', 'color'])
                ->toArray();
        });
    }
}
