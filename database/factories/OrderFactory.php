<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 5, 150);

        return [
            'buyer_id' => User::factory(),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'nexocoins_used' => 0,
            'total' => $subtotal,
            'currency' => 'USD',
            'total_in_currency' => $subtotal,
            'exchange_rate' => 1,
            'payment_method' => null,
            'payment_reference' => null,
            'paid_at' => null,
            'completed_at' => null,
            'ip_address' => fake()->ipv4(),
            'meta' => null,
        ];
    }
}
