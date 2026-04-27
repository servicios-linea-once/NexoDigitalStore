<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubscriptionRequest extends Model
{
    protected $fillable = [
        'ulid', 'seller_id', 'customer_email', 'plan_id', 'status',
        'admin_id', 'approved_at', 'admin_notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (SubscriptionRequest $request) {
            $request->ulid = (string) Str::ulid();
        });
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function customer()
    {
        return User::where('email', $this->customer_email)->first();
    }
}
