<?php

namespace App\Http\Controllers\Admin\Store\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PUT /admin/store/products/{ulid}
 */
class UpdateController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary) {}

    public function __invoke(Request $request, string $ulid): RedirectResponse
    {
        $product = Product::where('ulid', $ulid)->firstOrFail();

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'category_id'        => ['required', 'exists:categories,id'],
            'description'        => ['nullable', 'string', 'max:5000'],
            'short_description'  => ['nullable', 'string', 'max:500'],
            'platform'           => ['nullable', 'string', 'max:100'],
            'region'             => ['nullable', 'string', 'max:50'],
            'delivery_type'      => ['required', 'in:auto,manual,api'],
            'status'             => ['required', 'in:draft,active,paused'],
            'price_usd'          => ['required', 'numeric', 'min:0.01'],
            'price_pen'          => ['required', 'numeric', 'min:0.01'],
            'cashback_percent'   => ['nullable', 'numeric', 'min:0', 'max:50'],
            'cashback_amount_nt' => ['nullable', 'integer', 'min:0'],
            'is_featured'        => ['boolean'],
            'is_preorder'        => ['boolean'],
            'activation_guide'   => ['nullable', 'string'],
            'tags'               => ['nullable', 'array'],
            'new_images'         => ['nullable', 'array', 'max:6'],
            'new_images.*'       => ['file', 'image', 'max:4096'],
            'delete_images'      => ['nullable', 'array'],
            'delete_images.*'    => ['integer', 'exists:product_images,id'],
            // Variants validation
            'variants'           => ['nullable', 'array'],
            'variants.*.id'           => ['nullable', 'integer'],
            'variants.*.variant_name' => ['required', 'string', 'max:255'],
            'variants.*.price_usd'    => ['required', 'numeric', 'min:0'],
            'variants.*.price_pen'    => ['required', 'numeric', 'min:0'],
            'variants.*.status'       => ['required', 'in:active,paused,draft'],
        ]);

        DB::beginTransaction();
        try {
            if (! empty($validated['delete_images'])) {
                $toDelete = ProductImage::whereIn('id', $validated['delete_images'])
                    ->where('product_id', $product->id)->get();
                foreach ($toDelete as $img) {
                    if ($img->public_id) $this->cloudinary->delete($img->public_id);
                    $img->delete();
                }
            }

            $currentCount = $product->images()->count();
            foreach ($request->file('new_images', []) as $file) {
                if ($currentCount >= 6) break;
                try {
                    $result = $this->cloudinary->uploadProductImage($file, $product->slug);
                    ProductImage::create([
                        'product_id' => $product->id,
                        'url'        => $result['url'],
                        'public_id'  => $result['public_id'],
                        'is_cover'   => $currentCount === 0,
                        'sort_order' => $currentCount,
                    ]);
                    $currentCount++;
                } catch (\Exception $e) {
                    Log::error("Error uploading image: {$e->getMessage()}");
                }
            }

            $product->update([
                'name'               => $validated['name'],
                'category_id'        => $validated['category_id'],
                'description'        => $validated['description'] ?? null,
                'short_description'  => $validated['short_description'] ?? null,
                'platform'           => $validated['platform'] ?? null,
                'region'             => $validated['region'] ?? 'Global',
                'delivery_type'      => $validated['delivery_type'],
                'status'             => $validated['status'],
                'price_usd'          => $validated['price_usd'],
                'price_pen'          => $validated['price_pen'],
                'cashback_percent'   => $validated['cashback_percent'] ?? 0,
                'cashback_amount_nt' => $validated['cashback_amount_nt'] ?? 0,
                'is_featured'        => $validated['is_featured'] ?? false,
                'is_preorder'        => $validated['is_preorder'] ?? false,
                'activation_guide'   => $validated['activation_guide'] ?? null,
                'tags'               => $validated['tags'] ?? [],
            ]);

            // ── Sincronización de Variantes ────────────────────────────────
            $variantIds = [];
            foreach ($request->input('variants', []) as $vData) {
                $v = $product->variants()->updateOrCreate(
                    ['id' => $vData['id'] ?? null],
                    [
                        'variant_name' => $vData['variant_name'],
                        'price_usd'    => $vData['price_usd'],
                        'price_pen'    => $vData['price_pen'],
                        'status'       => $vData['status'],
                        // Copiar campos base del padre si son variantes simples
                        'seller_id'     => $product->seller_id,
                        'category_id'   => $product->category_id,
                        'name'          => $product->name, // Nombre base para búsqueda
                        'slug'          => $product->slug . '-' . \Illuminate\Support\Str::slug($vData['variant_name']),
                        'delivery_type' => $product->delivery_type,
                        'platform'      => $product->platform,
                        'region'        => $product->region,
                    ]
                );
                $variantIds[] = $v->id;
            }

            // Eliminar las que ya no están en la lista
            $product->variants()->whereNotIn('id', $variantIds)->delete();

            DB::commit();
            cache()->forget('nav_categories');
            return back()->with('success', 'Producto y variantes actualizados correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating product: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error crítico al guardar: ' . $e->getMessage()]);
        }
    }
}
