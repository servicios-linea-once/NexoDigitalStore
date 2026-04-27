<?php

namespace App\Http\Controllers\Admin\Store\Products;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * GET /admin/store/products/{ulid}/edit
 */
class EditController extends Controller
{
    public function __invoke(Request $request, string $ulid): Response
    {
        $product = Product::with(['category', 'images', 'variants'])
            ->where('ulid', $ulid)
            ->firstOrFail();

        return Inertia::render('Admin/Store/Products/Edit', [
            'product' => [
                'id'                 => $product->id,
                'ulid'               => $product->ulid,
                'name'               => $product->name,
                'slug'               => $product->slug,
                'status'             => $product->status,
                'category_id'        => $product->category_id,
                'description'        => $product->description,
                'short_description'  => $product->short_description,
                'platform'           => $product->platform,
                'region'             => $product->region,
                'delivery_type'      => $product->delivery_type,
                'price_usd'          => (float) $product->price_usd,
                'price_pen'          => (float) $product->price_pen,
                'cashback_percent'   => (int)   $product->cashback_percent,
                'cashback_amount_nt' => (int)   $product->cashback_amount_nt,
                'is_featured'        => (bool)  $product->is_featured,
                'is_preorder'        => (bool)  $product->is_preorder,
                'activation_guide'   => $product->activation_guide,
                'tags'               => $product->tags ?? [],
                'stock_count'        => (int) $product->stock_count,
                'keys_available'     => (int) $product->stock_count,
                'keys_used'          => (int) $product->total_sales,
                'is_active'          => $product->status === 'active',
                'images'             => $product->images->map(fn ($img) => [
                    'id'        => $img->id,
                    'url'       => $img->url,
                    'public_id' => $img->public_id,
                    'is_cover'  => (bool) $img->is_cover,
                ])->toArray(),
                'variants'           => $product->variants->map(fn ($v) => [
                    'id'           => $v->id,
                    'ulid'         => $v->ulid,
                    'variant_name' => $v->variant_name,
                    'price_usd'    => (float) $v->price_usd,
                    'price_pen'    => (float) $v->price_pen,
                    'stock_count'  => (int) $v->stock_count,
                    'status'       => $v->status,
                ])->toArray(),
            ],
            'categories' => Category::where('is_active', true)
                ->with(['children' => fn ($q) => $q->where('is_active', true)])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'icon']),
            'platforms' => config('nexo.catalog.platforms', []),
            'regions'   => config('nexo.catalog.regions', []),
        ]);
    }
}
