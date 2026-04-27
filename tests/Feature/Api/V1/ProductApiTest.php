<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected User $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['is_active' => true]);
        $this->seller = User::factory()->create(['role' => 'seller']);
    }

    // ── Product Listing Tests ───────────────────────────────────────────────

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'ulid', 'name', 'slug', 'platform', 'region',
                        'price_usd', 'price_pen', 'stock_count', 'is_featured', 'rating',
                        'category', 'cover_image',
                    ],
                ],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_products_excludes_inactive(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);
        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'draft',
            'stock_count' => 10,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertJsonPath('meta.total', 1);
    }

    public function test_products_excludes_out_of_stock(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);
        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 0,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertJsonPath('meta.total', 1);
    }

    public function test_products_can_be_filtered_by_search(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'name' => 'Steam Gift Card',
            'status' => 'active',
            'stock_count' => 10,
        ]);
        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'name' => 'PlayStation Plus',
            'status' => 'active',
            'stock_count' => 10,
        ]);

        $response = $this->getJson('/api/v1/products?search=Steam');

        $response->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Steam Gift Card');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $category2 = Category::factory()->create(['slug' => 'gaming']);

        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);
        Product::factory()->create([
            'category_id' => $category2->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);

        $response = $this->getJson('/api/v1/products?category=gaming');

        $response->assertJsonPath('meta.total', 1);
    }

    public function test_products_can_be_sorted_by_price(): void
    {
        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'price_usd' => 50.00,
            'status' => 'active',
            'stock_count' => 10,
        ]);
        Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'price_usd' => 10.00,
            'status' => 'active',
            'stock_count' => 10,
        ]);

        $response = $this->getJson('/api/v1/products?sort=price_asc');

        $response->assertJsonPath('data.0.price_usd', 10);
        $response->assertJsonPath('data.1.price_usd', 50);
    }

    public function test_products_show_promotion_discount(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'price_usd' => 100.00,
            'price_pen' => 380.00,
            'status' => 'active',
            'stock_count' => 10,
        ]);

        $promotion = Promotion::create([
            'seller_id' => $this->seller->id,
            'name' => '20% Off',
            'discount_type' => 'percent',
            'discount_value' => 20,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);
        $product->promotions()->attach($promotion->id);

        $response = $this->getJson("/api/v1/products/{$product->ulid}");

        $response->assertOk()
            ->assertJsonPath('price_usd', 100)
            ->assertJsonPath('discounted_price_usd', 80)
            ->assertJsonPath('active_promotion.name', '20% Off');
    }

    // ── Single Product Tests ────────────────────────────────────────────────

    public function test_can_view_single_product(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->ulid}");

        $response->assertOk()
            ->assertJsonStructure([
                'id', 'ulid', 'name', 'slug', 'description', 'short_description',
                'platform', 'region', 'delivery_type', 'price_usd', 'price_pen',
                'discounted_price_usd', 'discounted_price_pen',
                'stock_count', 'is_featured', 'rating', 'rating_count',
                'category', 'images', 'active_promotion', 'seller',
            ]);
    }

    public function test_product_shows_images(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'https://example.com/img1.jpg',
            'is_cover' => true,
            'sort_order' => 1,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'path' => 'https://example.com/img2.jpg',
            'is_cover' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->ulid}");

        $response->assertOk()
            ->assertJsonPath('images', [
                ['url' => 'https://example.com/img1.jpg', 'is_cover' => true],
                ['url' => 'https://example.com/img2.jpg', 'is_cover' => false],
            ]);
    }

    public function test_product_returns_404_if_not_found(): void
    {
        $response = $this->getJson('/api/v1/products/01ARZ3NDEKTSV4RRFFQ69G5FAV');

        $response->assertNotFound();
    }

    public function test_product_returns_404_if_inactive(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'draft',
            'stock_count' => 10,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->ulid}");

        $response->assertNotFound();
    }

    // ── Categories Tests ───────────────────────────────────────────────────

    public function test_can_list_categories(): void
    {
        Category::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'slug', 'icon', 'product_count']],
            ]);
    }

    // ── Pagination Tests ────────────────────────────────────────────────────

    public function test_products_are_paginated(): void
    {
        Product::factory()->count(25)->create([
            'category_id' => $this->category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);

        $response = $this->getJson('/api/v1/products?per_page=10');

        $response->assertOk()
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 3);

        $this->assertCount(10, $response->json('data'));
    }
}
