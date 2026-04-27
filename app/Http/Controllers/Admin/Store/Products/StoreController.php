<?php

namespace App\Http\Controllers\Admin\Store\Products;

use App\Http\Controllers\Controller;
use App\Models\DigitalKey;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * POST /admin/store/products
 */
class StoreController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'category_id'        => ['required', 'exists:categories,id'],
            'description'        => ['nullable', 'string', 'max:5000'],
            'short_description'  => ['nullable', 'string', 'max:500'],
            'platform'           => ['nullable', 'string', 'max:100'],
            'region'             => ['nullable', 'string', 'max:50'],
            'delivery_type'      => ['required', 'in:auto,manual,api'],
            'price_usd'          => ['required', 'numeric', 'min:0.01'],
            'price_pen'          => ['required', 'numeric', 'min:0.01'],
            'cashback_percent'   => ['nullable', 'numeric', 'min:0', 'max:50'],
            'cashback_amount_nt' => ['nullable', 'integer', 'min:0'],
            'is_active'          => ['nullable', 'boolean'],
            'is_featured'        => ['nullable', 'boolean'],
            'is_preorder'        => ['nullable', 'boolean'],
            'activation_guide'   => ['nullable', 'string'],
            'tags'               => ['nullable', 'array'],
            'tags.*'             => ['string', 'max:50'],
            'keys_text'          => ['nullable', 'string'],
            'images'             => ['nullable', 'array', 'max:6'],
            'images.*'           => ['file', 'image', 'max:4096', 'mimes:jpeg,jpg,png,webp'],
        ]);

        $isActive = filter_var($request->input('is_active', false), FILTER_VALIDATE_BOOLEAN);

        $product = Product::create([
            'seller_id'          => $request->user()->id,
            'category_id'        => $validated['category_id'],
            'name'               => $validated['name'],
            'slug'               => $this->uniqueSlug($validated['name']),
            'description'        => $validated['description'] ?? null,
            'short_description'  => $validated['short_description'] ?? null,
            'platform'           => $validated['platform'] ?? null,
            'region'             => $validated['region'] ?? 'Global',
            'delivery_type'      => $validated['delivery_type'],
            'price_usd'          => $validated['price_usd'],
            'price_pen'          => $validated['price_pen'],
            'cashback_percent'   => $validated['cashback_percent'] ?? 0,
            'cashback_amount_nt' => $validated['cashback_amount_nt'] ?? 0,
            'is_featured'        => $validated['is_featured'] ?? false,
            'is_preorder'        => $validated['is_preorder'] ?? false,
            'activation_guide'   => $validated['activation_guide'] ?? null,
            'tags'               => $validated['tags'] ?? [],
            'status'             => $isActive ? 'active' : 'draft',
        ]);

        // Process keys from textarea (one per line)
        if (! empty($request->keys_text)) {
            $lines = array_filter(array_map('trim', explode("\n", $request->keys_text)));
            foreach ($lines as $key) {
                DigitalKey::create([
                    'product_id' => $product->id,
                    'seller_id'  => $request->user()->id,
                    'key_value'  => $key,
                    'status'     => 'available',
                ]);
            }
        }

        foreach ($request->file('images', []) as $i => $file) {
            try {
                $result = $this->cloudinary->uploadProductImage($file, $product->slug);
                ProductImage::create([
                    'product_id' => $product->id,
                    'url'        => $result['url'],
                    'public_id'  => $result['public_id'],
                    'is_cover'   => $i === 0,
                    'sort_order' => $i,
                ]);
            } catch (\Exception $e) {
                Log::error("Error uploading image: {$e->getMessage()}");
            }
        }

        return redirect()->route('admin.store.products.edit', $product->ulid)
            ->with('success', 'Producto creado. Añade claves y actívalo cuando esté listo.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
