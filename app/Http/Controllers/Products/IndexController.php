<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Presenters\ProductIndexPagePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request, ProductIndexPagePresenter $presenter): Response
    {
        $filters = $request->only([
            'q', 'category', 'platform', 'region',
            'price_min', 'price_max', 'in_stock', 'sort',
        ]);

        $query = Product::with(['category:id,name,slug'])->active();

        if (! empty($filters['q'])) {
            $query->search($filters['q']);
        }

        if (! empty($filters['category'])) {
            $cat = Category::where('slug', $filters['category'])->first();
            if ($cat) {
                $ids = Category::where('parent_id', $cat->id)->pluck('id')->push($cat->id);
                $query->whereIn('category_id', $ids);
            }
        }

        if (! empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (! empty($filters['price_min'])) {
            $query->where('price_usd', '>=', $filters['price_min']);
        }
        if (! empty($filters['price_max'])) {
            $query->where('price_usd', '<=', $filters['price_max']);
        }

        if (! empty($filters['in_stock'])) {
            $query->where('stock_count', '>', 0);
        }

        match ($filters['sort'] ?? 'newest') {
            'popular'    => $query->orderByDesc('total_sales'),
            'rating'     => $query->orderByDesc('rating')->orderByDesc('rating_count'),
            'price_asc'  => $query->orderBy('price_usd'),
            'price_desc' => $query->orderByDesc('price_usd'),
            default      => $query->latest(),
        };

        $products = $query->paginate(24)->withQueryString();

        return Inertia::render('Products/Index', $presenter->present(
            $products,
            $this->parentCategoriesForFilter(),
            $filters,
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
