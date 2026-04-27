<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class User extends Authenticatable implements MustVerifyEmail, Searchable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'username',
        'avatar',
        'role',
        'provider',
        'provider_id',
        'is_active',
        'last_login_at',
        // OAuth
        'google_id',
        'steam_id',
        // 2FA
        'two_factor_secret',
        // Telegram linking
        'telegram_link_token',
        'telegram_link_token_expires_at',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'provider_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'telegram_link_token_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ── Boot ──────────────────────────────────────────────────────────────
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            $user->ulid = (string) Str::ulid();
            if (! $user->username) {
                $user->username = 'user_'.strtolower(Str::random(8));
            }
        });
    }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getAvatarUrlAttribute(): string
    {
        $avatar = $this->getAttributeFromArray('avatar');

        if ($avatar && str_starts_with($avatar, 'http')) {
            return $avatar;
        }

        return $avatar
            ? asset('storage/'.$avatar)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=6366f1&color=fff&bold=true';
    }

    // ── Role helpers (compatible with both legacy `role` field and Spatie) ──
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->hasRole('admin');
    }

    public function isSeller(): bool
    {
        return in_array($this->role, ['seller', 'admin']) || $this->hasRole(['seller', 'admin']);
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer' || $this->hasRole('buyer');
    }

    /**
     * Check permission via Spatie (preferred over Gate).
     * Usage: $user->hasPermissionTo('users.edit')
     */
    public function canDo(string $permission): bool
    {
        return $this->hasPermissionTo($permission);
    }

    // ── Relationships ─────────────────────────────────────────────────────
    // [PUNTO-2] sellerProfile() eliminado — Single-Vendor: la configuración de tienda
    // ahora vive en App\Models\StoreSetting (singleton en DB, independiente de usuarios).

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->with('plan')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')           // Free lifetime
                    ->orWhere('expires_at', '>', now()); // paid plans
            })
            ->latest();
    }

    /**
     * Returns the checkout discount % for this user's current subscription.
     * 0.0 if they have no active subscription or the plan has no discount.
     */
    public function subscriptionDiscount(): float
    {
        $sub = $this->activeSubscription;

        return $sub ? $sub->discountPercent() : 0.0;
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function licenseActivations()
    {
        return $this->hasMany(LicenseActivation::class);
    }

    public function telegramUser()
    {
        return $this->hasOne(TelegramUser::class);
    }

    public function twoFactorAuth()
    {
        return $this->hasOne(TwoFactorAuth::class);
    }

    public function getSearchResult(): SearchResult
    {
        $url = route('admin.users.show', $this->id);

        return new SearchResult(
            $this,
            $this->name,
            $url
        );
    }
}
