<?php

namespace App\Http\Controllers\Wishlist;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ToggleController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $user      = $request->user();
        $productId = $request->product_id;

        // Limpiamos la caché de la wishlist del usuario para que se refresque
        Cache::forget("user:{$user->id}:wishlist");

        // OPTIMIZACIÓN: Ejecutamos delete() directamente en la BD sin hacer SELECT primero.
        // Si elimina algo (devuelve 1), significa que ya estaba en la lista. ¡Más rápido!
        $deletedCount = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->delete();

        if ($deletedCount > 0) {
            return response()->json([
                'in_wishlist' => false,
                'message'     => 'Producto eliminado de tu lista de deseos.',
            ]);
        }

        Wishlist::create([
            'user_id'    => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json([
            'in_wishlist' => true,
            'message'     => 'Producto guardado en tu lista de deseos.',
        ]);
    }
}
