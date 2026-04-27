<?php

namespace App\Http\Controllers\Wishlist;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DestroyController extends Controller
{
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();

        Wishlist::where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        Cache::forget("user:{$user->id}:wishlist");

        return back()->with('success', 'Producto eliminado de tu lista de deseos.');
    }
}
