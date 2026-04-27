<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuth extends Model
{
    use HasFactory;

    protected $table = 'two_factor_auth';

    protected $fillable = [
        'user_id', 'secret', 'recovery_codes', 'is_enabled', 'enabled_at',
    ];

    protected $casts = [
        'secret'         => 'encrypted',
        'recovery_codes' => 'encrypted:array',
        'is_enabled'     => 'boolean',
        'enabled_at'     => 'datetime',
    ];

    protected $hidden = [
        'secret', 'recovery_codes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
