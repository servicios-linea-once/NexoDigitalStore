<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountController extends Controller
{
    public function __invoke(Request $request, CartService $cartService): JsonResponse
    {
        return response()->json([
            'count' => $cartService->getCount($request),
        ]);
    }
}
