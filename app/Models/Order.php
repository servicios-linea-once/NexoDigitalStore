<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid', 'buyer_id', 'status',
        'subtotal', 'discount_amount', 'nexocoins_used', 'total',
        'currency', 'total_in_currency', 'exchange_rate',
        'payment_method', 'payment_reference',
        'paid_at', 'completed_at', 'ip_address', 'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'nexocoins_used' => 'decimal:4',
        'total' => 'decimal:4',
        'total_in_currency' => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Order $order) {
            $order->ulid = (string) Str::ulid();
        });

        static::updated(function (Order $order) {
            if (! $order->isDirty('status') || $order->status !== 'completed') {
                return;
            }

            if ($order->meta['is_topup'] ?? false) {
                // [BUG-1 FIX] Usar WalletService en lugar de manipular balance directamente.
                // Garantiza firma HMAC-SHA256 y lockForUpdate() contra race conditions.
                $ntAmount = (int) ($order->meta['nt_amount'] ?? 0);
                $wallet   = $order->buyer?->wallet;

                if ($ntAmount > 0 && $wallet) {
                    try {
                        $walletService = app(\App\Services\WalletService::class);
                        $lockedWallet  = $walletService->lockForWrite($wallet->id);
                        $walletService->credit(
                            $lockedWallet,
                            $ntAmount,
                            'topup',
                            'Recarga de NexoTokens (Web)',
                            "Order:{$order->id}"
                        );
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error(
                            "[Order Observer] Top-up wallet credit failed for order #{$order->ulid}: " . $e->getMessage()
                        );
                    }
                }
            } else {
                // Notificación de compra normal completada
                $order->buyer->notify(new \App\Notifications\OrderCompletedNotification($order));
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // [PUNTO-1] disputes() eliminado — modelo Single-Vendor no necesita sistema de disputas
    // La tabla disputes queda en DB como registro histórico pero ya no se usa en código.

    // ── Status helpers ────────────────────────────────────────────────────
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
}
