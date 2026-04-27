<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserSubscription extends Model
{
    protected $fillable = [
        'ulid', 'user_id', 'plan_id', 'order_id', 'status',
        'payment_gateway', 'payment_reference', 'amount_paid', 'currency',
        'starts_at', 'expires_at', 'auto_renew', 'cancelled_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:4',
        'auto_renew' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',  // nullable = Free lifetime
        'cancelled_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (UserSubscription $sub) {
            $sub->ulid = (string) Str::ulid();
            $sub->auto_renew = false; // enforce no auto-renewal
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // null expires_at = Free lifetime plan (never expires)
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function daysRemaining(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        } // lifetime

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }

    /** Returns the checkout discount % granted by this subscription's plan. */
    public function discountPercent(): float
    {
        return $this->isActive() ? (float) ($this->plan?->discount_percent ?? 0) : 0.0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
