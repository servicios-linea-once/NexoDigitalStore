<?php

namespace App\Http\Controllers\Wishlist;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $productId = $request->query('product_id');
        $user = $request->user();

        if (! $user || ! $productId) {
            return response()->json(['in_wishlist' => false]);
        }

        // OPTIMIZACIÓN CON CACHÉ: En lugar de consultar SQL por cada botón de corazón,
        // guardamos un arreglo con los IDs de los productos en caché por 1 hora.
        $wishlistIds = Cache::remember("user:{$user->id}:wishlist", 3600, function () use ($user) {
            return Wishlist::where('user_id', $user->id)->pluck('product_id')->toArray();
        });

        // Verificación instantánea en memoria (O(1))
        $inWishlist = in_array((int) $productId, $wishlistIds);

        return response()->json(['in_wishlist' => $inWishlist]);
    }
}
