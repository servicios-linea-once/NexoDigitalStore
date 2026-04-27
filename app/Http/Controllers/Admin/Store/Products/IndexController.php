<?php

namespace App\Http\Controllers\Admin\Store\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * GET /admin/store/products
 */
class IndexController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $products = QueryBuilder::for(Product::class)
            ->with(['category', 'coverImage'])
            ->allowedFilters(
                AllowedFilter::partial('q', 'name'),
                AllowedFilter::partial('global', 'name'),
                'status',
                AllowedFilter::exact('category', 'category_id'),
                'platform',
                AllowedFilter::callback('has_stock', function ($query, $value) {
                    if ($value === 'yes') $query->where('stock_count', '>', 0);
                    if ($value === 'no')  $query->where('stock_count', '<=', 0);
                })
            )
            ->allowedSorts('name', 'price_usd', 'stock_count', 'total_sales', 'created_at')
            ->defaultSort('-created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Store/Products/Index', [
            'products' => $products->through(fn ($p) => [
                'id'          => $p->id,
                'ulid'        => $p->ulid,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'status'      => $p->status,
                'platform'    => $p->platform,
                'region'      => $p->region,
                'price_usd'   => (float) $p->price_usd,
                'stock_count' => (int) $p->stock_count,
                'total_sales' => (int) $p->total_sales,
                'rating'      => round((float) $p->rating, 1),
                'category'    => $p->category?->name,
                'cover_image' => $p->coverImage?->url,
            ]),
            'filters' => $request->only(['filter', 'sort']),
        ]);
    }
}
