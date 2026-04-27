<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['category:id,name,slug', 'coverImage', 'promotions'])
            ->active()
            ->inStock()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('platform', 'like', "%{$s}%")
            )
            ->when($request->category, fn ($q, $c) => $q->whereHas('category', fn ($cat) => $cat->where('slug', $c)))
            ->when($request->platform, fn ($q, $p) => $q->where('platform', $p))
            ->when($request->sort === 'price_asc',  fn ($q) => $q->orderBy('price_usd'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderByDesc('price_usd'))
            ->when($request->sort === 'newest',     fn ($q) => $q->latest())
            ->when(! $request->sort, fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('total_sales'))
            ->paginate($request->per_page ?? 20)
            ->withQueryString();

        return response()->json([
            'data' => ProductResource::collection($products->getCollection())->resolve(),
            'meta' => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    public function show(string $ulid): JsonResponse
    {
        $product = Product::with([
            'category:id,name,slug',
            'images',
            'promotions',
            'seller:id,name,avatar',
        ])->where('ulid', $ulid)->active()->firstOrFail();

        return response()->json((new ProductDetailResource($product))->resolve());
    }
}
