<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'features',
        'price_usd', 'price_pen', 'duration_days', 'discount_percent',
        'is_active', 'is_visible',
    ];

    protected $casts = [
        'features'         => 'array',
        'price_usd'        => 'decimal:4',
        'price_pen'        => 'decimal:4',
        'duration_days'    => 'integer',
        'discount_percent' => 'decimal:2',
        'is_active'        => 'boolean',
        'is_visible'       => 'boolean',
    ];

    public function isFree(): bool
    {
        return (float) $this->price_usd === 0.0;
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
