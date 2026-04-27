<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'ulid', 'order_id', 'user_id',
        'gateway',                    // paypal | mercadopago | nexotokens
        'gateway_order_id',           // external order/preference ID
        'gateway_transaction_id',     // capture/transaction ID after completion
        'status',                     // pending | completed | failed | refunded
        'amount', 'currency', 'amount_usd',
        'fee', 'gateway_response', 'ip_address', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'amount_usd' => 'decimal:4',
        'fee' => 'decimal:4',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Payment $payment) {
            $payment->ulid = (string) Str::ulid();
        });
    }

    // ── Relationships ──────────────────────────────────────────────────────
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
