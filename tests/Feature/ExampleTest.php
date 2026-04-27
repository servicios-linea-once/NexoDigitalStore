<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
            'is_featured' => true,
            'parent_id' => null,
        ]);
        $seller = User::factory()->create(['role' => 'seller']);
        Product::factory()->create([
            'category_id' => $category->id,
            'seller_id' => $seller->id,
            'status' => 'active',
            'is_featured' => true,
            'stock_count' => 10,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
