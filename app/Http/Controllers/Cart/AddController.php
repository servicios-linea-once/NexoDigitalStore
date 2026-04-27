<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddController extends Controller
{
    public function __invoke(Request $request, CartService $cartService): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ulid' => ['required', 'string', 'regex:/^[0-7][0-9A-HJKMNP-TV-Z]{25}$/'], // Validar formato ULID
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $product = Product::with(['coverImage', 'promotions'])
            ->where('ulid', $request->ulid)
            ->active()
            ->inStock()
            ->firstOrFail();

        $item = $cartService->add($request, $product);
        $message = "{$product->name} añadido al carrito.";

        if ($request->expectsJson()) {
            return response()->json([
                'message'    => $message,
                'cart_count' => $cartService->getCount($request),
                'item'       => $item,
            ]);
        }

        return back()->with('success', $message);
    }
}
