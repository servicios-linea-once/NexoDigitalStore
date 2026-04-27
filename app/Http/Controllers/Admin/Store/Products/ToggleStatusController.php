<?php

namespace App\Http\Controllers\Admin\Store\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PATCH /admin/store/products/{ulid}/toggle-status
 */
class ToggleStatusController extends Controller
{
    public function __invoke(Request $request, string $ulid): RedirectResponse
    {
        $product   = Product::where('ulid', $ulid)->firstOrFail();
        $newStatus = $product->status === 'active' ? 'paused' : 'active';

        if ($newStatus === 'active') {
            if ($product->stock_count < 1) {
                return back()->withErrors(['error' => 'Agrega claves antes de activar el producto.']);
            }
            if ($product->images()->count() < 1) {
                return back()->withErrors(['error' => 'Agrega al menos una imagen antes de activar.']);
            }
        }

        $product->update(['status' => $newStatus]);

        return back()->with('success', $newStatus === 'active'
            ? 'Producto activado — ya visible en el catálogo.'
            : 'Producto pausado — no visible en el catálogo.'
        );
    }
}
