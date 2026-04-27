<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\ReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * OrderCompletedNotification — [PUNTO-5] Adjunta recibo PDF automáticamente.
 *
 * Requiere:
 *   composer require barryvdh/laravel-dompdf
 *
 * Canales: mail (con PDF adjunto) + database (para campana de notificaciones).
 */
class OrderCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Despachar solo después del commit de la transacción padre.
     * Evita que la notificación se envíe si el DB::transaction del checkout hace rollback.
     */
    public bool $afterCommit = true;

    public function __construct(
        protected Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $itemCount = $this->order->items()->count();
        $total     = number_format($this->order->total, 2);
        $currency  = $this->order->currency;
        $orderId   = strtoupper(substr($this->order->ulid, -8));

        $mail = (new MailMessage)
            ->subject("✅ Pedido #{$orderId} completado — Nexo eStore")
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("Tu pedido **#{$orderId}** ha sido procesado exitosamente.")
            ->line("**{$itemCount} producto(s)** — Total: \${$total} {$currency}")
            ->line('Tus claves digitales ya están disponibles en tu cuenta.')
            ->action('Ver mis pedidos', url('/orders/'.$this->order->ulid))
            ->line('_Por seguridad, las claves solo se muestran dentro de tu cuenta._')
            ->salutation('¡Gracias por comprar en Nexo eStore! 🎮');

        // ── [PUNTO-5] Adjuntar recibo PDF ──────────────────────────────────────
        try {
            // Genera el PDF y lo adjunta como datos inline (sin escribir en disco permanente)
            $pdfContent = app(ReceiptService::class)->content($this->order);
            $shortId    = strtoupper(substr($this->order->ulid, -8));

            $mail->attachData(
                $pdfContent,
                "nexo_recibo_{$shortId}.pdf",
                ['mime' => 'application/pdf']
            );
        } catch (\Exception $e) {
            // Si falla la generación del PDF, el email se envía igualmente sin adjunto
            Log::error("[ReceiptService] Error generando PDF para orden #{$this->order->ulid}: ".$e->getMessage());
        }
        // ──────────────────────────────────────────────────────────────────────

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'order_completed',
            'order_id'    => $this->order->id,
            'order_ulid'  => $this->order->ulid,
            'total'       => (float) $this->order->total,
            'currency'    => $this->order->currency,
            'items_count' => $this->order->items()->count(),
            'message'     => 'Tu pedido ha sido completado. Tus claves están listas.',
        ];
    }
}
