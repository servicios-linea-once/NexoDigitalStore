<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\CapturePayPalOrderRequest;
use App\Http\Requests\Payment\PayPalOrderRequest;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPalController
 *
 * Handles PayPal Checkout SDK + Orders API v2 flow:
 *   1. createOrder  → creates a PayPal order, returns order_id to frontend
 *   2. captureOrder → captures payment, marks our order as completed
 *   3. Webhook      → handled by Webhook\PayPalWebhookController
 */
class PayPalController extends Controller
{
    private string $baseUrl;

    private string $clientId;

    private string $secret;

    public function __construct()
    {
        $mode = config('nexo.payments.paypal.mode', 'sandbox');
        $this->baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $this->clientId = (string) config('nexo.payments.paypal.client_id', '');
        $this->secret = (string) config('nexo.payments.paypal.client_secret', '');
    }

    // ── Step 1: Create PayPal Order ────────────────────────────────────────
    public function createOrder(PayPalOrderRequest $request): JsonResponse
    {

        $order = Order::where('ulid', $request->order_ulid)
            ->where('buyer_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $token = $this->getAccessToken();
        if (! $token) {
            return response()->json(['error' => 'PayPal no disponible. Intenta más tarde.'], 503);
        }

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->ulid,
                    'description' => 'Nexo Digital Store — Compra de productos digitales',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($order->total, 2, '.', ''),
                    ],
                ]],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                            'brand_name' => 'Nexo Digital Store',
                            'locale' => 'es-PE',
                            'user_action' => 'PAY_NOW',
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::error('PayPal createOrder failed', ['body' => $response->body()]);

            return response()->json(['error' => 'No se pudo crear la orden en PayPal.'], 500);
        }

        $ppOrderId = $response->json('id');

        // Store PayPal order ID on our payment record
        Payment::updateOrCreate(
            ['order_id' => $order->id, 'gateway' => 'paypal'],
            [
                'user_id'          => $request->user()->id,
                'gateway_order_id' => $ppOrderId,
                'amount' => $order->total,
                'currency' => $order->currency ?? 'USD',
                'status' => 'pending',
                'ip_address' => request()->ip(),
            ]
        );

        return response()->json(['paypal_order_id' => $ppOrderId]);
    }

    // ── Step 2: Capture PayPal Order ───────────────────────────────────────
    public function captureOrder(CapturePayPalOrderRequest $request): JsonResponse
    {

        $order = Order::where('ulid', $request->order_ulid)
            ->where('buyer_id', $request->user()->id)
            ->firstOrFail();

        $token = $this->getAccessToken();
        if (! $token) {
            return response()->json(['error' => 'PayPal no disponible.'], 503);
        }

        $response = Http::withToken($token)
            ->withBody('{}', 'application/json')
            ->post("{$this->baseUrl}/v2/checkout/orders/{$request->paypal_order_id}/capture");

        if (! $response->successful() || $response->json('status') !== 'COMPLETED') {
            Log::error('PayPal capture failed', ['body' => $response->body()]);

            return response()->json(['error' => 'El pago no pudo completarse.'], 402);
        }

        // Mark order and payment as completed
        $captureId = $response->json('purchase_units.0.payments.captures.0.id');
        $order->update([
            'status' => 'completed',
            'payment_reference' => $captureId,
            'paid_at' => now(),
            'completed_at' => now(),
        ]);

        Payment::where('order_id', $order->id)->where('gateway', 'paypal')
            ->update([
                'status' => 'completed',
                'gateway_transaction_id' => $captureId,
                'paid_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'order_ulid' => $order->ulid,
            'redirect' => route('orders.show', $order->ulid),
        ]);
    }

    // ── Private: Get Bearer Token ──────────────────────────────────────────
    private function getAccessToken(): ?string
    {
        $response = Http::withBasicAuth($this->clientId, $this->secret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        return $response->successful() ? $response->json('access_token') : null;
    }
}
