<?php

namespace Database\Factories;

use App\Models\DigitalKey;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DigitalKeyFactory extends Factory
{
    protected $model = DigitalKey::class;

    public function definition(): array
    {
        $rawKey = strtoupper(fake()->bothify('KEY-####-????'));

        return [
            'product_id' => Product::factory(),
            'seller_id' => User::factory(),
            'key_value' => encrypt($rawKey),
            'key_hash' => hash('sha256', $rawKey),
            'status' => 'available',
            'order_item_id' => null,
            'reserved_by' => null,
            'reserved_at' => null,
            'reserved_until' => null,
            'sold_at' => null,
            'notes' => null,
            'is_license' => false,
            'max_activations' => 1,
            'activation_count' => 0,
            'current_activations' => 0,
            'license_expires_at' => null,
            'license_type' => null,
        ];
    }
}
