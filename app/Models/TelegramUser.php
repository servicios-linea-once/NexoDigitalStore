<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'telegram_id', 'username', 'first_name', 'last_name',
        'language_code', 'is_linked', 'link_token', 'link_token_expires_at',
        'preferred_currency', 'state', 'cart', 'notifications_enabled',
        'last_interaction_at',
    ];

    protected $casts = [
        'is_linked' => 'boolean',
        'notifications_enabled' => 'boolean',
        'cart' => 'array',
        'link_token_expires_at' => 'datetime',
        'last_interaction_at' => 'datetime',
    ];

    protected $hidden = ['link_token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isLinked(): bool
    {
        return $this->is_linked && $this->user_id !== null;
    }

    public function addToCart(int $productId, int $qty = 1): void
    {
        $cart = $this->cart ?? [];
        $cart[$productId] = ($cart[$productId] ?? 0) + $qty;
        $this->update(['cart' => $cart]);
    }

    public function clearCart(): void
    {
        $this->update(['cart' => [], 'state' => 'idle']);
    }

    public function setState(string $state): void
    {
        $this->update(['state' => $state, 'last_interaction_at' => now()]);
    }
}
