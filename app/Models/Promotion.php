<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'seller_id',
        'name',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'discount_value' => 'float',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeActiveNow($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($dates) {
                $dates->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($dates) {
                $dates->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }
}
