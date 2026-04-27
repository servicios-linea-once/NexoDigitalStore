<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Data\ProductData;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = QueryBuilder::for(Product::class)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                'status',
                'category_id'
            )
            ->allowedSorts('name', 'price_usd', 'created_at')
            ->paginate(20);

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price_usd' => 'required|numeric|min:0',
            'price_pen' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,draft,paused',
        ]);

        $product = Product::create(array_merge($validated, [
            'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
            'ulid' => (string) Str::ulid(),
            'seller_id' => auth()->id(),
        ]));

        return response()->json(ProductData::from($product), 201);
    }

    public function show(string $ulid): JsonResponse
    {
        $product = Product::where('ulid', $ulid)->firstOrFail();
        return response()->json(ProductData::from($product));
    }

    public function update(Request $request, string $ulid): JsonResponse
    {
        $product = Product::where('ulid', $ulid)->firstOrFail();
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price_usd' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:active,draft,paused',
        ]);

        $product->update($validated);

        return response()->json(ProductData::from($product));
    }

    public function destroy(string $ulid): JsonResponse
    {
        $product = Product::where('ulid', $ulid)->firstOrFail();
        $product->delete();

        return response()->json(['message' => 'Producto eliminado']);
    }
}
