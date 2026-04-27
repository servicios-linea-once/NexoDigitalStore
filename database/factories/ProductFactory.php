<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'parent_id' => null,
            'seller_id' => User::factory(),
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'variant_name' => null,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 999),
            'description' => fake()->paragraph(),
            'short_description' => fake()->sentence(),
            'cover_image' => null,
            'platform' => fake()->randomElement(['Steam', 'PlayStation', 'Xbox', 'Nintendo']),
            'region' => fake()->randomElement(['Global', 'LATAM', 'US']),
            'delivery_type' => fake()->randomElement(['auto', 'manual', 'api']),
            'activation_guide' => fake()->sentence(),
            'status' => 'active',
            'price_usd' => fake()->randomFloat(2, 5, 150),
            'price_pen' => fake()->randomFloat(2, 18, 550),
            'cashback_percent' => fake()->randomFloat(2, 0, 10),
            'cashback_amount_nt' => fake()->numberBetween(0, 30),
            'stock_count' => fake()->numberBetween(1, 50),
            'max_activations_per_key' => 1,
            'is_featured' => false,
            'is_preorder' => false,
            'preorder_release_date' => null,
            'total_sales' => fake()->numberBetween(0, 1000),
            'rating' => fake()->randomFloat(2, 0, 5),
            'rating_count' => fake()->numberBetween(0, 200),
            'tags' => ['digital'],
            'meta' => null,
        ];
    }
}
