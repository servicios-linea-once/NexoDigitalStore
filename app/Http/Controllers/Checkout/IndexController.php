<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request, CartService $cartService): Response|RedirectResponse
    {
        $cartItems = $cartService->getItems($request);

        if (empty($cartItems)) {
            return redirect()->route('cart.index')->with('info', 'Tu carrito está vacío.');
        }

        $totals = $cartService->getTotals($request);

        // Opt: Usamos el scopeActive() del modelo Currency
        $currencies = Currency::active()
            ->orderBy('is_default', 'desc')
            ->get(['code', 'name', 'symbol', 'rate_to_usd']);

        $defaultCurrency = $currencies->firstWhere('is_default', true)?->code ?? 'PEN';

        // User wallet balance
        $walletBalance = $request->user()->wallet?->balance ?? 0;
        $walletBalanceUsd = round($walletBalance * config('nexo.token.rate_to_usd', 0.10), 2);

        // Active subscription
        $activeSub = $request->user()->activeSubscription()->with('plan')->first();

        // PayPal config
        $ppMode = config('nexo.payments.paypal.mode', 'sandbox');
        $ppClientId = $ppMode === 'sandbox'
            ? config('nexo.payments.paypal.sandbox_client_id', config('nexo.payments.paypal.client_id'))
            : config('nexo.payments.paypal.client_id');

        return Inertia::render('Checkout/Index', [
            'cart'            => $cartItems,
            'totals'          => $totals,
            'currencies'      => $currencies,
            'defaultCurrency' => $defaultCurrency,
            'walletBalance'   => (float) $walletBalance,
            'walletBalanceUsd'=> $walletBalanceUsd,
            'paypalClientId'  => $ppClientId,
            'paypalMode'      => $ppMode,
            'mpPublicKey'     => config('nexo.payments.mercadopago.public_key'),
            'subscription'    => $activeSub ? [
                'plan'             => $activeSub->plan?->name,
                'plan_slug'        => $activeSub->plan?->slug,
                'discount_percent' => $activeSub->discountPercent(),
            ] : null,
        ]);
    }
}
