<?php

namespace App\Http\Controllers\Admin\Store\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * DELETE /admin/store/products/{ulid}
 */
class DestroyController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary) {}

    public function __invoke(Request $request, string $ulid): RedirectResponse
    {
        $product = Product::with(['images'])->where('ulid', $ulid)->firstOrFail();

        if ($product->digitalKeys()->whereIn('status', ['sold', 'reserved'])->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar un producto con ventas activas.']);
        }

        foreach ($product->images as $img) {
            if ($img->public_id) $this->cloudinary->delete($img->public_id);
        }

        $product->delete();

        return redirect()->route('admin.store.products.index')
            ->with('success', 'Producto archivado correctamente.');
    }
}
