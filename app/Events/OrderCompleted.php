<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderCompleted Event — [PUNTO-4] Laravel Reverb / WebSockets
 *
 * Disparado por los webhooks (MercadoPago, PayPal) cuando el pago es confirmado.
 * Permite al frontend Vue 3 detectar la confirmación en tiempo real sin polling.
 *
 * Canal: private-order.{buyer_id}  (autenticado via Sanctum)
 * Escucha en Vue: Echo.private(`order.${userId}`).listen('OrderCompleted', cb)
 */
class OrderCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Order $order) {}

    /**
     * Canal privado del comprador — solo él puede escucharlo.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("order.{$this->order->buyer_id}"),
        ];
    }

    /**
     * Nombre del evento que escucha el frontend:
     * Echo.private(...).listen('OrderCompleted', callback)
     */
    public function broadcastAs(): string
    {
        return 'OrderCompleted';
    }

    /**
     * Payload enviado al frontend — solo lo mínimo necesario.
     * Las claves digitales NO se envían por WebSocket (seguridad).
     * El frontend debe redirigir al usuario a la página de la orden para verlas.
     */
    public function broadcastWith(): array
    {
        return [
            'order_ulid'   => $this->order->ulid,
            'status'       => $this->order->status,
            'total'        => (float) $this->order->total,
            'currency'     => $this->order->currency,
            'items_count'  => $this->order->items()->count(),
            'completed_at' => $this->order->completed_at?->toISOString(),
            'message'      => '¡Tu pago fue confirmado! Tus claves están disponibles.',
        ];
    }
}
