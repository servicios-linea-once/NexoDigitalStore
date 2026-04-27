<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AuditLog;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * AdminProductController — Admin oversight of all marketplace products.
 * Admins can view all products (any seller), change status, and soft-delete.
 */
class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::with(['seller:id,name', 'category:id,name', 'coverImage'])
            ->when($request->q,      fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhereHas('seller', fn ($u) => $u->where('name', 'like', "%{$s}%"))
            )
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->seller_id, fn ($q, $id) => $q->where('seller_id', $id))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn ($p) => [
                'id'          => $p->id,
                'ulid'        => $p->ulid,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'status'      => $p->status,
                'platform'    => $p->platform,
                'price_usd'   => (float) $p->price_usd,
                'stock_count' => (int) $p->stock_count,
                'total_sales' => (int) $p->total_sales,
                'rating'      => round((float) $p->rating, 1),
                'seller'      => $p->seller ? ['id' => $p->seller->id, 'name' => $p->seller->name] : null,
                'category'    => $p->category?->name,
                'cover_image' => $p->coverImage?->url,  // eager loaded — no lazy load error
                'created_at'  => $p->created_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters'  => $request->only(['q', 'status', 'seller_id']),
        ]);
    }

    /** Admin can change status of any product */
    public function updateStatus(Request $request, string $ulid): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:draft,active,paused']]);

        $product = Product::where('ulid', $ulid)->firstOrFail();
        $old = $product->status;
        $product->update(['status' => $request->status]);

        AuditLog::record('admin_product_status_changed', Auth::id(), [
            'product_id' => $product->id,
            'old_status' => $old,
            'new_status' => $request->status,
        ]);

        Cache::forget('home:payload');

        return back()->with('success', "Producto {$request->status}.");
    }

    /** Admin soft-delete of any product */
    public function destroy(string $ulid): RedirectResponse
    {
        $product = Product::where('ulid', $ulid)->firstOrFail();

        AuditLog::record('admin_product_deleted', Auth::id(), [
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'seller_id'    => $product->seller_id,
        ]);

        $product->delete();
        Cache::forget('home:payload');

        return back()->with('success', 'Producto eliminado del marketplace.');
    }
}
