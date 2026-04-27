<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid', 'order_id', 'product_id', 'seller_id',
        'product_name', 'product_cover', 'quantity',
        'unit_price', 'total_price', 'seller_earnings',
        'cashback_amount', 'delivery_status', 'delivered_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:4',
        'seller_earnings' => 'decimal:4',
        'cashback_amount' => 'decimal:4',
        'delivered_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (OrderItem $item) {
            $item->ulid = (string) Str::ulid();
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function digitalKey()
    {
        return $this->hasOne(DigitalKey::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
