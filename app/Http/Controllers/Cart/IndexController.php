<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request, CartService $cartService): Response
    {
        return Inertia::render('Cart/Index', [
            'cart'   => $cartService->getItems($request),
            'totals' => $cartService->getTotals($request),
        ]);
    }
}
