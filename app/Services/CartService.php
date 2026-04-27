<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CartService — Gestión del carrito de compras basado en sesión.
 *
 * IMPORTANTE: El carrito se almacena en la sesión del navegador (Request->session()),
 * no en base de datos. Esto significa:
 *
 * - ✅ El carrito persiste mientras la sesión sea válida (default: 2 horas)
 * - ✅ El carrito NO persiste entre navegadores o dispositivos
 * - ✅ El carrito se limpia cuando la sesión expira
 * - ❌ Si necesitas persistencia cross-device, considera usar Redis sessions
 *
 * El carrito es un array asociativo en sesión con estructura:
 * [
 *   'product_ulid' => [
 *       'ulid' => 'xxx',
 *       'id' => 123,
 *       'name' => 'Product Name',
 *       'price_usd' => 19.99,
 *       'discounted_price_usd' => 17.99,
 *       ...
 *   ],
 *   ...
 * ]
 *
 * Métodos disponibles:
 * - getItems()    → Array de items del carrito
 * - getTotals()   → Totales, ahorros y descuentos aplicados
 * - add()         → Agregar/actualizar producto en carrito
 * - remove()      → Eliminar producto del carrito
 * - clear()       → Limpiar carrito completamente
 * - getCount()    → Cantidad de items únicos en carrito
 */
class CartService
{
    public function getItems(Request $request): array
    {
        return array_values($request->session()->get('cart', []));
    }

    public function getTotals(Request $request): array
    {
        $items = $this->getItems($request);
        $subtotalUsd = 0.0;
        $subtotalPen = 0.0;
        $savingsUsd = 0.0;
        $savingsPen = 0.0;

        foreach ($items as $item) {
            $subtotalUsd += $item['discounted_price_usd'];
            $subtotalPen += $item['discounted_price_pen'];
            $savingsUsd  += ($item['price_usd'] - $item['discounted_price_usd']);
            $savingsPen  += ($item['price_pen'] - $item['discounted_price_pen']);
        }

        // Descuento por suscripción
        $subDiscountPct = 0.0;
        $subDiscountAmtUsd = 0.0;
        $subDiscountAmtPen = 0.0;
        $subPlanName = null;

        if (Auth::check()) {
            $user = Auth::user();
            $subDiscountPct = $user->subscriptionDiscount();
            if ($subDiscountPct > 0) {
                $subDiscountAmtUsd = round($subtotalUsd * $subDiscountPct / 100, 2);
                $subDiscountAmtPen = round($subtotalPen * $subDiscountPct / 100, 2);
            }
            $sub = $user->activeSubscription;
            $subPlanName = $sub?->plan?->name;
        }

        $subtotalNt = $subtotalUsd / config('nexo.token.rate_to_usd', 0.10);

        return [
            'count'                     => count($items),
            'subtotal_usd'              => round($subtotalUsd, 2),
            'subtotal_pen'              => round($subtotalPen, 2),
            'savings_usd'               => round($savingsUsd, 2),
            'savings_pen'               => round($savingsPen, 2),
            'subtotal_nt'               => round($subtotalNt, 2),
            'subscription_discount_usd' => $subDiscountAmtUsd,
            'subscription_discount_pen' => $subDiscountAmtPen,
            'subscription_pct'          => $subDiscountPct,
            'subscription_plan'         => $subPlanName,
        ];
    }

    public function add(Request $request, Product $product): array
    {
        $cart = $request->session()->get('cart', []);
        $item = $this->productToCartItem($product);

        $cart[$product->ulid] = $item;
        $request->session()->put('cart', $cart);

        return $item;
    }

    public function remove(Request $request, string $ulid): void
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$ulid]);
        $request->session()->put('cart', $cart);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget('cart');
    }

    public function getCount(Request $request): int
    {
        return count($request->session()->get('cart', []));
    }

    private function productToCartItem(Product $p): array
    {
        $name = $p->name;
        // Si es una variante, combinamos el nombre del padre con el de la variante
        if ($p->parent_id && $p->variant_name) {
            $parent = $p->parent;
            $name = ($parent ? $parent->name : $p->name) . ' - ' . $p->variant_name;
        }

        return [
            'ulid'                 => $p->ulid,
            'id'                   => $p->id,
            'slug'                 => $p->slug,
            'name'                 => $name,
            'variant_name'         => $p->variant_name,
            'is_variant'           => (bool) $p->parent_id,
            'platform'             => $p->platform,
            'region'               => $p->region,
            'cover_image'          => $p->coverImage?->url ?? $p->parent?->coverImage?->url ?? null,
            'price_usd'            => (float) $p->price_usd,
            'price_pen'            => (float) $p->price_pen,
            'discounted_price_usd' => (float) $p->discounted_price_usd,
            'discounted_price_pen' => (float) $p->discounted_price_pen,
            'active_promotion'     => $p->active_promotion ? ['name' => $p->active_promotion->name] : null,
            'cashback_amount_nt'   => (int) $p->cashback_amount_nt,
            'quantity'             => 1,
        ];
    }
}
