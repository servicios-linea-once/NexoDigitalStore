<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'rate_to_usd', 'is_active', 'is_default'];

    protected $casts = [
        'rate_to_usd' => 'decimal:6',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $fromRate = static::where('code', $from)->value('rate_to_usd') ?? 1;
        $toRate = static::where('code', $to)->value('rate_to_usd') ?? 1;

        $usd = $amount / $fromRate;

        return round($usd * $toRate, 4);
    }
}
