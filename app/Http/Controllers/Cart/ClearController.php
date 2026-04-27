<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClearController extends Controller
{
    public function __invoke(Request $request, CartService $cartService): RedirectResponse
    {
        $cartService->clear($request);

        return redirect()->route('cart.index')->with('info', 'Carrito vacío.');
    }
}
