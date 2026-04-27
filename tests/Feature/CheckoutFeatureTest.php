<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DigitalKey;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base category and product
        $category = Category::factory()->create(['is_active' => true]);
        
        $seller = User::factory()->create(['role' => 'seller']);
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'seller_id' => $seller->id,
            'price_usd' => 10.00,
            'price_pen' => 38.00,
            'status' => 'active',
            'stock_count' => 1,
            'delivery_type' => 'auto',
        ]);

        DigitalKey::create([
            'product_id' => $this->product->id,
            'seller_id' => $seller->id,
            'key_value' => 'TEST-KEY-123', // auto encrypts
            'key_hash' => hash('sha256', 'TEST-KEY-123' . $this->product->id),
            'status' => 'available',
        ]);

        $this->buyer = User::factory()->create(['role' => 'buyer']);
        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 0]);
    }

    public function test_cannot_checkout_empty_cart()
    {
        $response = $this->actingAs($this->buyer)
            ->post('/checkout', [
                'payment_method' => 'nexotokens',
                'currency' => 'USD'
            ]);

        $response->assertRedirect('/cart');
        $response->assertSessionHas('error');
    }

    public function test_can_checkout_with_nexotokens()
    {
        // Give buyer enough NT (10 USD = 100 NT at $0.10)
        $this->buyer->wallet->update(['balance' => 200]);

        // Mock cart in session
        $cartItem = [
            'id' => $this->product->id,
            'name' => $this->product->name,
            'price_usd' => 10.00,
            'price_pen' => 38.00,
            'discounted_price_usd' => 10.00,
            'discounted_price_pen' => 38.00,
            'cover_image' => null,
            'cashback_percent' => 5,
            'cashback_amount_nt' => 0,
        ];

        session()->put('cart', [$this->product->id => $cartItem]);

        $response = $this->actingAs($this->buyer)
            ->post('/checkout', [
                'payment_method' => 'nexotokens',
                'currency' => 'USD',
                'nt_amount' => 100 // 10 USD worth
            ]);

        $this->assertDatabaseHas('orders', [
            'buyer_id' => $this->buyer->id,
            'status' => 'completed',
            'payment_method' => 'nexotokens',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $this->product->id,
        ]);

        // Check key status
        $this->assertDatabaseHas('digital_keys', [
            'product_id' => $this->product->id,
            'status' => 'sold'
        ]);

        // Stock decreased
        $this->assertEquals(0, $this->product->fresh()->stock_count);
        $this->assertEquals(1, $this->product->fresh()->total_sales);

        // Wallet balance updated: 200 - 100 (spent) + cashback (5% of 10 USD = 0.5 USD = 5 NT) = 105 NT
        $this->assertEquals(105, $this->buyer->wallet->fresh()->balance);

        $response->assertSessionMissing('cart');
    }

    public function test_cannot_checkout_insufficient_nt()
    {
        $this->buyer->wallet->update(['balance' => 50]); // Not enough

        session()->put('cart', [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price_usd' => 10.00,
                'price_pen' => 38.00,
                'discounted_price_usd' => 10.00,
                'discounted_price_pen' => 38.00,
                'cover_image' => null,
                'cashback_percent' => 5,
                'cashback_amount_nt' => 0,
            ]
        ]);

        $response = $this->actingAs($this->buyer)
            ->post('/checkout', [
                'payment_method' => 'nexotokens',
                'currency' => 'USD',
                'nt_amount' => 100
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(50, $this->buyer->wallet->fresh()->balance);
        $this->assertEquals(1, $this->product->fresh()->stock_count); // Stock untouched
    }

    public function test_cannot_checkout_out_of_stock()
    {
        $this->buyer->wallet->update(['balance' => 200]);

        // Set stock to 0
        $this->product->update(['stock_count' => 0]);

        session()->put('cart', [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price_usd' => 10.00,
                'price_pen' => 38.00,
                'discounted_price_usd' => 10.00,
                'discounted_price_pen' => 38.00,
                'cover_image' => null,
                'cashback_percent' => 5,
                'cashback_amount_nt' => 0,
            ]
        ]);

        $response = $this->actingAs($this->buyer)
            ->post('/checkout', [
                'payment_method' => 'nexotokens',
                'currency' => 'USD',
                'nt_amount' => 100
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(200, $this->buyer->wallet->fresh()->balance);
        $this->assertEquals(0, $this->product->fresh()->stock_count);
    }

    public function test_cannot_checkout_no_available_key()
    {
        $this->buyer->wallet->update(['balance' => 200]);

        // Mark the key as unavailable
        DigitalKey::where('product_id', $this->product->id)->update(['status' => 'sold']);

        session()->put('cart', [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price_usd' => 10.00,
                'price_pen' => 38.00,
                'discounted_price_usd' => 10.00,
                'discounted_price_pen' => 38.00,
                'cover_image' => null,
                'cashback_percent' => 5,
                'cashback_amount_nt' => 0,
            ]
        ]);

        $response = $this->actingAs($this->buyer)
            ->post('/checkout', [
                'payment_method' => 'nexotokens',
                'currency' => 'USD',
                'nt_amount' => 100
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(200, $this->buyer->wallet->fresh()->balance);
    }

    public function test_cannot_checkout_with_invalid_currency()
    {
        $this->buyer->wallet->update(['balance' => 200]);

        session()->put('cart', [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price_usd' => 10.00,
                'price_pen' => 38.00,
                'discounted_price_usd' => 10.00,
                'discounted_price_pen' => 38.00,
                'cover_image' => null,
                'cashback_percent' => 5,
                'cashback_amount_nt' => 0,
            ]
        ]);

        $response = $this->actingAs($this->buyer)
            ->post('/checkout', [
                'payment_method' => 'nexotokens',
                'currency' => 'INVALID_CURRENCY',
                'nt_amount' => 100
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(200, $this->buyer->wallet->fresh()->balance);
    }

    public function test_checkout_clears_cart_on_success()
    {
        $this->buyer->wallet->update(['balance' => 200]);

        session()->put('cart', [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'price_usd' => 10.00,
                'price_pen' => 38.00,
                'discounted_price_usd' => 10.00,
                'discounted_price_pen' => 38.00,
                'cover_image' => null,
                'cashback_percent' => 5,
                'cashback_amount_nt' => 0,
            ]
        ]);

        $this->actingAs($this->buyer)
            ->post('/checkout', [
                'payment_method' => 'nexotokens',
                'currency' => 'USD',
                'nt_amount' => 100
            ]);

        // Cart should be cleared
        $this->assertEmpty(session()->get('cart', []));
    }
}
