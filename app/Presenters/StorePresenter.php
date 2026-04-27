<?php

namespace App\Presenters;

use App\Models\StoreSetting;
use App\Models\User;

class StorePresenter
{
    public function fromSeller(?User $seller): ?array
    {
        if (! $seller) {
            return null;
        }

        $settings = StoreSetting::public();

        return [
            'name' => $settings['store_name'] ?? $seller->name,
            'avatar' => $seller->avatar_url,
            'rating' => round((float) ($settings['store_rating'] ?? 0), 1),
            'sales' => (int) ($settings['store_sales'] ?? 0),
            'is_verified' => (bool) ($settings['store_verified'] ?? false),
            'verified' => (bool) ($settings['store_verified'] ?? false),
        ];
    }
}
