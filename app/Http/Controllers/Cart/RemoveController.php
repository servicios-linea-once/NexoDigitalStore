<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RemoveController extends Controller
{
    public function __invoke(Request $request, string $ulid, CartService $cartService): RedirectResponse|JsonResponse
    {
        $cartService->remove($request, $ulid);

        if ($request->expectsJson()) {
            return response()->json(['cart_count' => $cartService->getCount($request)]);
        }

        return back()->with('success', 'Producto eliminado del carrito.');
    }
}
