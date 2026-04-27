<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletTransaction extends Model
{
    protected $fillable = [
        'ulid', 'wallet_id', 'user_id', 'type', 'reason', 'amount',
        'balance_after', 'reference', 'note', 'hash', 'previous_hash'
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (WalletTransaction $tx) {
            $tx->ulid = (string) Str::ulid();

            // Lógica de encadenamiento (Blockchain-like)
            $lastTx = static::where('wallet_id', $tx->wallet_id)->latest('id')->first();
            $tx->previous_hash = $lastTx ? $lastTx->hash : null;
            $tx->hash = $tx->generateHash();
        });
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Cryptographic Security ──────────────────────────────────────────────

    /**
     * Genera un hash único para esta transacción basado en sus datos y el hash anterior.
     */
    public function generateHash(): string
    {
        $payload = implode('|', [
            $this->ulid,
            $this->wallet_id,
            $this->type,
            number_format($this->amount, 4, '.', ''),
            number_format($this->balance_after, 4, '.', ''),
            $this->previous_hash ?? 'genesis'
        ]);

        return hash_hmac('sha256', $payload, config('app.key'));
    }

    /**
     * Verifica si la transacción ha sido alterada.
     */
    public function verifyIntegrity(): bool
    {
        return hash_equals($this->hash, $this->generateHash());
    }
}
