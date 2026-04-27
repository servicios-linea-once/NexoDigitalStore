<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\MercadoPagoOrderRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MercadoPagoController
 *
 * Flow:
 *   1. createPreference → creates a MP preference, returns init_point for redirect
 *   2. success/failure/pending callbacks → update order status
 *   3. Webhook IPN → handled by Webhook\MercadoPagoWebhookController
 */
class MercadoPagoController extends Controller
{
    private string $accessToken;

    private string $baseUrl = 'https://api.mercadopago.com';

    public function __construct()
    {
        $this->accessToken = (string) config('nexo.payments.mercadopago.access_token', '');
    }

    // ── Step 1: Create MP Preference ───────────────────────────────────────
    public function createPreference(MercadoPagoOrderRequest $request): JsonResponse
    {

        $order = Order::with('items.product')
            ->where('ulid', $request->order_ulid)
            ->where('buyer_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Always send a single consolidated item to ensure discounts (NT, Subscriptions) are reflected in the final total
        $items = [[
            'id' => 'ORDER-'.$order->ulid,
            'title' => ($order->meta['is_topup'] ?? false) ? 'Recarga de NexoTokens (NT)' : 'Pago de Orden #'.substr($order->ulid, -8),
            'quantity' => 1,
            'unit_price' => round((float) $order->total_in_currency, 2),
            'currency_id' => strtoupper($order->currency ?? 'USD'),
        ]];

        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/checkout/preferences", [
                'items' => $items,
                'external_reference' => $order->ulid,
                'back_urls' => [
                    'success' => route('payment.mp.success'),
                    'failure' => route('payment.mp.failure'),
                    'pending' => route('payment.mp.pending'),
                ],
                'auto_return' => 'approved',
                'statement_descriptor' => 'NEXO DIGITAL STORE',
                'notification_url' => route('webhook.mercadopago'),
                'expires' => true,
                'expiration_date_to' => now()->addHours(2)->toIso8601String(),
            ]);

        if (! $response->successful()) {
            Log::error('MercadoPago createPreference failed', ['body' => $response->body()]);

            return response()->json(['error' => 'No se pudo crear la preferencia de pago.'], 500);
        }

        $prefId = $response->json('id');
        $initPoint = $response->json('init_point'); // production
        $sandbox = $response->json('sandbox_init_point');

        Payment::updateOrCreate(
            ['order_id' => $order->id, 'gateway' => 'mercadopago'],
            [
                'user_id'          => $request->user()->id,
                'gateway_order_id' => $prefId,
                'amount' => $order->total,
                'currency' => $order->currency ?? 'USD',
                'status' => 'pending',
                'ip_address' => request()->ip(),
            ]
        );

        return response()->json([
            'preference_id' => $prefId,
            'init_point' => app()->environment('production') ? $initPoint : $sandbox,
        ]);
    }

    // ── Callbacks ─────────────────────────────────────────────────────
    public function success(Request $request): RedirectResponse
    {
        // SECURITY: Do NOT complete the order here.
        // This redirect can be forged by a malicious user.
        // The IPN webhook (MercadoPagoWebhookController) is the authoritative source.
        $orderUlid = $request->external_reference;

        return redirect()->route('orders.show', $orderUlid ?? '')
            ->with('info', 'Tu pago está siendo procesado. Te notificaremos cuando se confirme.');
    }

    public function failure(Request $request): RedirectResponse
    {
        $orderUlid = $request->external_reference;
        if ($orderUlid) {
            Order::where('ulid', $orderUlid)->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        }

        return redirect()->route('checkout.index')
            ->with('error', 'El pago fue rechazado. Por favor intenta con otro método.');
    }

    public function pending(Request $request): RedirectResponse
    {
        return redirect()->route('orders.index')
            ->with('warning', 'Tu pago está en proceso. Te notificaremos cuando se confirme.');
    }
}
