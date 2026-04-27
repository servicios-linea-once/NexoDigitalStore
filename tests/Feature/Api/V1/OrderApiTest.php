<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\DigitalKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $seller;
    protected Product $product;
    protected string $buyerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['role' => 'seller']);

        $category = Category::factory()->create(['is_active' => true]);
        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'seller_id' => $this->seller->id,
            'price_usd' => 10.00,
            'price_pen' => 38.00,
            'cashback_amount_nt' => 5,
            'status' => 'active',
            'stock_count' => 5,
            'delivery_type' => 'automatic',
        ]);

        DigitalKey::create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'key_value' => 'TEST-KEY-123',
            'key_hash' => hash('sha256', 'TEST-KEY-123'),
            'status' => 'available',
        ]);

        $this->buyer = User::factory()->create(['role' => 'buyer']);
        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 0]);

        // Create free subscription
        $freePlan = SubscriptionPlan::factory()->create(['slug' => 'free']);
        UserSubscription::create([
            'user_id' => $this->buyer->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => null,
        ]);

        $this->buyerToken = $this->buyer->createToken('api-mobile')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->buyerToken}"];
    }

    // ── Order Creation Tests ───────────────────────────────────────────────

    public function test_authenticated_user_can_create_order(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/orders', [
                'currency' => 'USD',
                'payment_method' => 'nexotokens',
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'order' => ['ulid', 'status', 'total_usd', 'total_nt'],
                'payment' => [
                    'method', 'total_usd', 'total_pen', 'total_nt',
                    'wallet_balance_nt', 'has_sufficient_balance',
                ],
                'expires_at',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.status', 'pending')
            ->assertJsonPath('payment.method', 'nexotokens');

        $this->assertDatabaseHas('orders', [
            'buyer_id' => $this->buyer->id,
            'status' => 'pending',
            'payment_method' => 'nexotokens',
        ]);
    }

    public function test_order_calculation_includes_prices_and_nt(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/orders', [
                'currency' => 'USD',
                'payment_method' => 'nexotokens',
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        // 10 USD = 100 NT at rate 0.10
        $response->assertJsonPath('payment.total_usd', 10.00);
        $response->assertJsonPath('payment.total_nt', 100.00);
    }

    public function test_order_calculates_subscription_discount(): void
    {
        // Create premium subscription with 10% discount
        $premiumPlan = SubscriptionPlan::factory()->create([
            'slug' => 'premium',
            'discount_percent' => 10,
        ]);
        $this->buyer->subscriptions()->update(['status' => 'expired']);
        UserSubscription::create([
            'user_id' => $this->buyer->id,
            'plan_id' => $premiumPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/orders', [
                'currency' => 'USD',
                'payment_method' => 'nexotokens',
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        // 10 USD - 10% = 9 USD
        $response->assertJsonPath('payment.total_usd', 9.00);
        $response->assertJsonPath('payment.savings_usd', 1.00);
    }

    public function test_order_fails_with_invalid_product(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/orders', [
                'currency' => 'USD',
                'payment_method' => 'nexotokens',
                'items' => [
                    ['product_id' => 99999],
                ],
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_order_fails_with_out_of_stock_product(): void
    {
        $this->product->update(['stock_count' => 0]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/orders', [
                'currency' => 'USD',
                'payment_method' => 'nexotokens',
                'items' => [
                    ['product_id' => $this->product->id],
                ],
            ]);

        $response->assertStatus(400);
    }

    public function test_order_reserves_stock_on_creation(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/orders', [
                'currency' => 'USD',
                'payment_method' => 'nexotokens',
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 2],
                ],
            ]);

        $this->assertEquals(3, $this->product->fresh()->stock_count);
    }

    public function test_order_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'currency' => 'USD',
            'payment_method' => 'nexotokens',
            'items' => [
                ['product_id' => $this->product->id],
            ],
        ]);

        $response->assertStatus(401);
    }

    // ── Order Payment Tests ─────────────────────────────────────────────────

    public function test_user_can_pay_order_with_sufficient_nt(): void
    {
        $this->buyer->wallet->update(['balance' => 200]); // 200 NT = 20 USD

        // Create order first
        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'status' => 'pending',
            'subtotal' => 10.00,
            'total' => 10.00,
            'currency' => 'USD',
            'total_in_currency' => 10.00,
            'exchange_rate' => 1.0,
            'payment_method' => 'nexotokens',
            'meta' => ['total_nt' => 100],
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00,
            'cashback_amount' => 5,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/orders/{$order->ulid}/pay");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'order' => ['ulid', 'status', 'total_usd', 'total_nt'],
                'wallet' => ['new_balance_nt', 'debited_nt'],
                'delivered_keys',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.status', 'completed')
            ->assertJsonPath('wallet.debited_nt', 100.00);

        $this->assertEquals(100, $this->buyer->fresh()->wallet->balance);
    }

    public function test_payment_fails_with_insufficient_balance(): void
    {
        $this->buyer->wallet->update(['balance' => 50]); // Only 50 NT

        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'status' => 'pending',
            'total' => 10.00,
            'currency' => 'USD',
            'meta' => ['total_nt' => 100],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/orders/{$order->ulid}/pay");

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('payment.required_nt', 100.00)
            ->assertJsonPath('payment.available_nt', 50.00);
    }

    public function test_expired_order_cannot_be_paid(): void
    {
        $this->buyer->wallet->update(['balance' => 200]);

        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'status' => 'pending',
            'total' => 10.00,
            'currency' => 'USD',
            'meta' => ['total_nt' => 100],
            'created_at' => now()->subMinutes(31), // Expired
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/orders/{$order->ulid}/pay");

        $response->assertStatus(410)
            ->assertJsonPath('order.status', 'expired');
    }

    public function test_digital_keys_are_delivered_on_payment(): void
    {
        $this->buyer->wallet->update(['balance' => 200]);

        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'status' => 'pending',
            'subtotal' => 10.00,
            'total' => 10.00,
            'currency' => 'USD',
            'total_in_currency' => 10.00,
            'payment_method' => 'nexotokens',
            'meta' => ['total_nt' => 100],
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00,
            'cashback_amount' => 5,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/orders/{$order->ulid}/pay");

        $response->assertOk()
            ->assertJsonPath('delivered_keys.0.key', 'TEST-KEY-123');
    }

    // ── Order Listing Tests ─────────────────────────────────────────────────

    public function test_user_can_list_their_orders(): void
    {
        Order::factory()->count(3)->create(['buyer_id' => $this->buyer->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['ulid', 'status', 'total_usd', 'total_nt', 'item_count']],
                'meta' => ['total', 'current_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 3);
    }

    public function test_user_cannot_see_other_users_orders(): void
    {
        $otherUser = User::factory()->create(['role' => 'buyer']);
        Order::factory()->create(['buyer_id' => $otherUser->id]);
        Order::factory()->create(['buyer_id' => $this->buyer->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/orders');

        $response->assertJsonPath('meta.total', 1);
    }

    // ── Order Detail Tests ─────────────────────────────────────────────────

    public function test_user_can_view_order_detail(): void
    {
        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'status' => 'completed',
            'subtotal' => 10.00,
            'total' => 10.00,
            'currency' => 'USD',
            'total_in_currency' => 10.00,
            'payment_method' => 'nexotokens',
            'nexocoins_used' => 100,
            'paid_at' => now(),
            'completed_at' => now(),
            'meta' => ['total_nt' => 100],
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00,
            'cashback_amount' => 5,
            'delivery_status' => 'delivered',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/orders/{$order->ulid}");

        $response->assertOk()
            ->assertJsonStructure([
                'order' => [
                    'ulid', 'status',
                    'subtotal_usd', 'subtotal_pen',
                    'discount_amount_usd', 'discount_amount_pen',
                    'nexocoins_used_nt', 'total_usd', 'total_pen', 'total_nt',
                    'currency', 'exchange_rate', 'payment_method',
                    'paid_at', 'completed_at', 'expires_at',
                    'items' => ['delivered', 'pending', 'total_count'],
                    'cashback_total_nt', 'created_at',
                ],
            ]);
    }

    public function test_order_404_for_other_users_order(): void
    {
        $otherUser = User::factory()->create(['role' => 'buyer']);
        $order = Order::factory()->create(['buyer_id' => $otherUser->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/orders/{$order->ulid}");

        $response->assertNotFound();
    }

    // ── Payment Status Tests ───────────────────────────────────────────────

    public function test_can_check_payment_status(): void
    {
        $order = Order::create([
            'buyer_id' => $this->buyer->id,
            'status' => 'pending',
            'total' => 10.00,
            'currency' => 'USD',
            'meta' => ['total_nt' => 100],
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/orders/{$order->ulid}/payment-status");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'order' => [
                    'ulid', 'status', 'payment_method', 'total_usd', 'total_nt',
                    'wallet_balance_nt', 'can_pay', 'is_expired', 'expires_at',
                ],
            ]);
    }
}
