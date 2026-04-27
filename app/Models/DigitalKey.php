<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DigitalKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid', 'product_id', 'seller_id', 'key_value', 'key_hash',
        'status', 'order_item_id', 'reserved_by',
        'reserved_at', 'reserved_until', 'sold_at', 'notes',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'reserved_until' => 'datetime',
        'sold_at' => 'datetime',
        'license_expires_at' => 'datetime',
        'is_license' => 'boolean',
    ];

    // Encrypt key_value automatically (security)
    public function setKeyValueAttribute(string $value): void
    {
        $this->attributes['key_value'] = encrypt($value);
    }

    public function getKeyValueAttribute(string $value): string
    {
        try {
            return decrypt($value);
        } catch (\Exception) {
            return $value;
        }
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (DigitalKey $key) {
            $key->ulid = (string) Str::ulid();
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function activations()
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function activeActivations()
    {
        return $this->hasMany(LicenseActivation::class)->active();
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    public function canActivate(): bool
    {
        return $this->status === 'sold'
            && $this->current_activations < $this->max_activations;
    }

    public function hasRemainingSlots(): bool
    {
        return $this->current_activations < $this->max_activations;
    }

    // ── Scopes ───────────────────────────────────────────────────────────
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeExpiredReservations($query)
    {
        return $query->where('status', 'reserved')
            ->where('reserved_until', '<', now());
    }
}
