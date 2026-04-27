<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LicenseActivation extends Model
{
    protected $fillable = [
        'ulid', 'digital_key_id', 'user_id', 'order_item_id',
        'machine_id', 'machine_name', 'os', 'device_type',
        'ip_address', 'hostname', 'status',
        'activated_at', 'expires_at', 'deactivated_at', 'deactivation_reason',
        'activation_token', 'last_seen_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = ['activation_token'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (LicenseActivation $la) {
            $la->ulid = (string) Str::ulid();
            $la->activated_at = $la->activated_at ?? now();
            $la->activation_token = encrypt(Str::random(64));
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────
    public function digitalKey()
    {
        return $this->belongsTo(DigitalKey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function deactivate(string $reason = 'user_request'): void
    {
        $this->update([
            'status' => 'deactivated',
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
        ]);

        // Decrement counter on the key
        $this->digitalKey->decrement('current_activations');
    }

    public function heartbeat(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function daysUntilExpiry(): ?int
    {
        return $this->expires_at
            ? max(0, (int) now()->diffInDays($this->expires_at, false))
            : null;
    }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
