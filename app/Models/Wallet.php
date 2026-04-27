<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['ulid', 'user_id', 'balance', 'locked_balance', 'currency', 'signature'];

    protected $casts = [
        'balance' => 'decimal:4',
        'locked_balance' => 'decimal:4',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Wallet $wallet) {
            $wallet->ulid = (string) Str::ulid();
            $wallet->currency = 'NT'; // NexoTokens
        });

        static::saving(function (Wallet $wallet) {
            $wallet->signature = $wallet->generateSignature();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function getAvailableBalanceAttribute(): float
    {
        return (float) ($this->balance - $this->locked_balance);
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    // ── Cryptographic Security ──────────────────────────────────────────────

    public function generateSignature(): string
    {
        $payload = $this->ulid . number_format($this->balance, 4, '.', '') . number_format($this->locked_balance, 4, '.', '');
        return hash_hmac('sha256', $payload, config('app.key'));
    }

    public function verifySignature(): bool
    {
        // If it's a new wallet without signature yet, consider it valid until saved.
        if (empty($this->signature)) {
            return true;
        }

        return hash_equals($this->signature, $this->generateSignature());
    }

    public function assertNotCompromised(): void
    {
        if (!$this->verifySignature()) {
            throw new \App\Exceptions\WalletCompromisedException($this->ulid);
        }
    }
}
