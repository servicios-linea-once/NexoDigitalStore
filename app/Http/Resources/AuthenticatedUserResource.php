<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar_url,
            'is_active' => (bool) $this->is_active,
            'two_factor_enabled' => (bool) ($this->twoFactorAuth?->is_enabled ?? false),
            'wallet' => $this->whenLoaded('wallet', fn () => [
                'id' => $this->wallet?->id,
                'balance' => $this->wallet ? (float) $this->wallet->balance : 0.0,
                'locked_balance' => $this->wallet ? (float) $this->wallet->locked_balance : 0.0,
                'currency' => $this->wallet?->currency,
            ]),
        ];
    }
}
