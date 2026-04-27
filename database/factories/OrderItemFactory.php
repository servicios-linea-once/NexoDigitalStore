<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(4, 5, 150);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'seller_id' => User::factory(),
            'product_name' => fake()->words(3, true),
            'product_cover' => fake()->imageUrl(),
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice,
            'cashback_amount' => 0,
            'delivery_status' => 'pending',
            'delivery_type' => 'automatic',
            'delivery_note' => null,
            'seller_paid' => false,
            'seller_paid_at' => null,
            'delivered_at' => null,
        ];
    }
}
