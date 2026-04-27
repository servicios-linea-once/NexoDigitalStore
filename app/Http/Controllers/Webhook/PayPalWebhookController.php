<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\WalletService;     // [REF-2] Centraliza operaciones de wallet con HMAC
use App\Events\OrderCompleted;      // [PUNTO-4] Reverb broadcast
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PayPal Webhook Controller
 *
 * Verifies and processes PayPal webhook events:
 *   - PAYMENT.CAPTURE.COMPLETED → mark order completed
 *   - PAYMENT.CAPTURE.DENIED    → mark order failed
 *   - CHECKOUT.ORDER.APPROVED   → (optional, used with hosted pages)
 */
class PayPalWebhookController extends Controller
{
    // [REF-2] WalletService inyectado — fuente de verdad para firmas HMAC-SHA256
    public function __construct(private readonly WalletService $walletService) {}
    public function handle(Request $request): Response
    {
        $webhookId = config('nexo.payments.paypal.webhook_id', '');
        $payload = $request->getContent();
        $headers = $request->headers->all();

        // ── Verify signature ───────────────────────────────────────
        if (! $this->verifySignature($webhookId, $payload, $headers)) {
            Log::warning('PayPal webhook: invalid signature');

            return response('Forbidden', 403);
        }

        $event = $request->input('event_type');
        $resource = $request->input('resource', []);

        Log::info('PayPal webhook received', ['event' => $event, 'id' => $resource['id'] ?? null]);

        match ($event) {
            'PAYMENT.CAPTURE.COMPLETED' => $this->handleCaptureCompleted($resource),
            'PAYMENT.CAPTURE.DENIED' => $this->handleCaptureDenied($resource),
            'PAYMENT.CAPTURE.REFUNDED' => $this->handleCaptureRefunded($resource),
            default => null,
        };

        return response('OK', 200);
    }

    private function handleCaptureCompleted(array $resource): void
    {
        $captureId = $resource['id'] ?? null;
        $ref = $resource['custom_id'] ?? $resource['invoice_id'] ?? null;

        $order = $ref
            ? Order::where('ulid', $ref)->first()
            : Order::whereHas('payments', fn ($q) => $q->where('gateway_order_id', $captureId))->first();

        if (! $order || $order->isCompleted()) {
            return;
        }

        $order->update([
            'status'            => 'completed',
            'payment_reference' => $captureId,
            'paid_at'           => now(),
            'completed_at'      => now(),
        ]);

        Payment::where('order_id', $order->id)->where('gateway', 'paypal')
            ->update([
                'status'                 => 'completed',
                'gateway_transaction_id' => $captureId,
                'paid_at'               => now(),
            ]);

        // Deliver reserved keys, grant cashback, increment sales
        $this->fulfillOrderItems($order);

        // [PUNTO-4] Broadcast via Laravel Reverb — notifica al buyer en tiempo real
        broadcast(new OrderCompleted($order->fresh()))->afterCommit();

        Log::info("[PayPal Webhook] Orden #{$order->ulid} completada y broadcast enviado.");
    }

    /**
     * Convert reserved keys to sold, deliver items, increment sales, grant cashback.
     * Shared fulfillment logic for all PayPal capture events.
     */
    private function fulfillOrderItems(Order $order): void
    {
        $buyer = $order->buyer;

        foreach ($order->items()->with(['product', 'digitalKey'])->get() as $item) {
            // Finalise reserved key
            if ($item->digitalKey && $item->digitalKey->status === 'reserved') {
                $item->digitalKey->update([
                    'status'  => 'sold',
                    'sold_at' => now(),
                ]);
            }

            // Deliver if automatic type
            if ($item->delivery_status === 'pending') {
                $deliveryType = $item->product?->delivery_type;
                if ($deliveryType === 'automatic' || $deliveryType === 'auto') {
                    $item->update([
                        'delivery_status' => 'delivered',
                        'delivered_at'    => now(),
                    ]);
                }
            }

            // Increment total_sales
            if ($item->product_id) {
                \App\Models\Product::where('id', $item->product_id)->increment('total_sales');
            }

            // Grant cashback NT via WalletService
            $cashbackNt = (float) $item->cashback_amount;
            if ($cashbackNt > 0 && $buyer?->wallet) {
                try {
                    // [REF-2] WalletService::credit() — evita manipulación manual de balance
                    $wallet = $this->walletService->lockForWrite($buyer->wallet->id);
                    $this->walletService->credit(
                        $wallet,
                        $cashbackNt,
                        'cashback',
                        "Cashback: {$item->product_name}",
                        "OrderItem:{$item->id}"
                    );
                } catch (\Exception $e) {
                    Log::error('Cashback grant failed (PayPal)', ['order_item' => $item->id, 'error' => $e->getMessage()]);
                }
            }
        }
    }

    private function handleCaptureDenied(array $resource): void
    {
        $captureId = $resource['id'] ?? null;
        $order = Order::whereHas('payments', fn ($q) => $q->where('gateway_transaction_id', $captureId))->first();
        if (! $order) {
            return;
        }

        $order->update(['status' => 'cancelled']);
        Payment::where('order_id', $order->id)->where('gateway', 'paypal')
            ->update(['status' => 'failed']);
    }

    private function handleCaptureRefunded(array $resource): void
    {
        $originalCaptureId = $resource['links'][0]['href'] ?? null;
        // For simplicity, find by refund amount / order
        // In production you'd parse the links to find the original capture
        Log::info('PayPal refund received', ['resource' => $resource]);
    }

    private function verifySignature(string $webhookId, string $payload, array $headers): bool
    {
        // In production: call PayPal's verify-webhook-signature API
        // For sandbox/dev: skip verification if webhook_id is not set
        if (empty($webhookId)) {
            return true; // dev mode
        }

        try {
            $mode = config('paypal.mode', 'sandbox');
            $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

            // Get token
            $tokenResponse = Http::withBasicAuth(
                (string) config('nexo.payments.paypal.client_id', ''),
                (string) config('nexo.payments.paypal.client_secret', '')
            )->asForm()->post("{$baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

            if (! $tokenResponse->successful()) {
                return false;
            }

            $verifyResponse = Http::withToken($tokenResponse->json('access_token'))
                ->post("{$baseUrl}/v1/notifications/verify-webhook-signature", [
                    'auth_algo' => $headers['paypal-auth-algo'][0] ?? '',
                    'cert_url' => $headers['paypal-cert-url'][0] ?? '',
                    'transmission_id' => $headers['paypal-transmission-id'][0] ?? '',
                    'transmission_sig' => $headers['paypal-transmission-sig'][0] ?? '',
                    'transmission_time' => $headers['paypal-transmission-time'][0] ?? '',
                    'webhook_id' => $webhookId,
                    'webhook_event' => json_decode($payload, true),
                ]);

            return $verifyResponse->json('verification_status') === 'SUCCESS';
        } catch (\Exception $e) {
            Log::error('PayPal signature verification failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
