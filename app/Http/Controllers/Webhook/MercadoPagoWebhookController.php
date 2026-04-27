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
 * MercadoPago IPN Webhook
 *
 * Events handled:
 *   - payment → payment.status: approved, rejected, in_process
 *   - merchant_order → merchant_order.status
 */
class MercadoPagoWebhookController extends Controller
{
    // [REF-2] WalletService inyectado — fuente de verdad para firmas HMAC-SHA256
    public function __construct(private readonly WalletService $walletService) {}
    public function handle(Request $request): Response
    {
        // ── Verify MP signature (x-signature header) ───────────────
        if (! $this->verifySignature($request)) {
            Log::warning('MercadoPago webhook: invalid signature');

            return response('Forbidden', 403);
        }

        $type = $request->input('type') ?? $request->input('topic');
        $id = $request->input('data.id') ?? $request->input('id');

        Log::info('MercadoPago webhook', compact('type', 'id'));

        if ($type === 'payment' && $id) {
            $this->handlePayment($id);
        }

        return response('OK', 200);
    }

    private function handlePayment(string $paymentId): void
    {
        try {
            $response = Http::withToken(config('nexo.payments.mercadopago.access_token', config('mercadopago.access_token')))
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if (! $response->successful()) {
                Log::error('MP payment fetch failed', ['id' => $paymentId]);

                return;
            }

            $payment = $response->json();
            $status = $payment['status'];       // approved, rejected, in_process
            $ref = $payment['external_reference'] ?? null;

            if (! $ref) {
                return;
            }

            $order = Order::where('ulid', $ref)->first();
            if (! $order) {
                return;
            }

            match ($status) {
                'approved' => $this->completeOrder($order, $paymentId, $payment),
                'rejected' => $order->update(['status' => 'cancelled']),
                'refunded' => $order->update(['status' => 'refunded']),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error('MP payment processing error', ['error' => $e->getMessage()]);
        }
    }

    private function completeOrder(Order $order, string $mpPaymentId, array $mpPayment): void
    {
        if ($order->isCompleted()) {
            return;
        }

        $order->update([
            'status'            => 'completed',
            'payment_reference' => $mpPaymentId,
            'paid_at'           => now(),
            'completed_at'      => now(),
        ]);

        Payment::where('order_id', $order->id)->where('gateway', 'mercadopago')
            ->update([
                'status'                 => 'completed',
                'gateway_transaction_id' => $mpPaymentId,
                'paid_at'               => now(),
            ]);

        // Deliver reserved keys and grant cashback
        $this->fulfillOrderItems($order);

        // [PUNTO-4] Broadcast via Laravel Reverb — notifica al buyer en tiempo real
        broadcast(new OrderCompleted($order->fresh()))->afterCommit();

        Log::info("[MP Webhook] Orden #{$order->ulid} completada y broadcast enviado.");
    }

    /**
     * Convert reserved keys to sold, deliver items, increment sales, grant cashback.
     */
    private function fulfillOrderItems(Order $order): void
    {
        $ntRate = config('nexo.token.rate_to_usd', 0.10);
        $buyer  = $order->buyer;

        foreach ($order->items()->with(['product', 'digitalKey'])->get() as $item) {
            // Convert reserved key to sold
            if ($item->digitalKey && $item->digitalKey->status === 'reserved') {
                $item->digitalKey->update([
                    'status'  => 'sold',
                    'sold_at' => now(),
                ]);
            }

            // Deliver the item if automatic
            if ($item->delivery_status === 'pending') {
                $deliveryType = $item->product?->delivery_type;
                if ($deliveryType === 'automatic' || $deliveryType === 'auto') {
                    $item->update([
                        'delivery_status' => 'delivered',
                        'delivered_at'    => now(),
                    ]);
                }
            }

            // Increment product total_sales
            if ($item->product_id) {
                \App\Models\Product::where('id', $item->product_id)->increment('total_sales');
            }

            // Grant cashback to buyer via WalletService
            $cashbackNt = (float) $item->cashback_amount;
            if ($cashbackNt > 0 && $buyer?->wallet) {
                try {
                    // [REF-2] Usar WalletService::credit() en lugar de operaciones manuales
                    // Garantiza que la firma HMAC-SHA256 se calcule bajo la misma fuente de verdad
                    $wallet = $this->walletService->lockForWrite($buyer->wallet->id);
                    $this->walletService->credit(
                        $wallet,
                        $cashbackNt,
                        'cashback',
                        "Cashback: {$item->product_name}",
                        "OrderItem:{$item->id}"
                    );
                } catch (\Exception $e) {
                    Log::error('Cashback grant failed', ['order_item' => $item->id, 'error' => $e->getMessage()]);
                }
            }
        }
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('nexo.payments.mercadopago.webhook_secret', config('mercadopago.webhook_secret'));
        if (empty($secret)) {
            return true;
        } // dev mode

        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');
        $dataId = $request->input('data.id');

        if (! $xSignature) {
            return false;
        }

        // Extract ts and v1 from x-signature
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$k, $v] = explode('=', trim($part), 2);
            $parts[$k] = $v;
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }
}
