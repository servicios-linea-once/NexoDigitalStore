<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\DigitalKey;
use App\Models\LicenseActivation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $seller;
    protected Product $product;
    protected DigitalKey $digitalKey;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['role' => 'seller']);
        $category = Category::factory()->create(['is_active' => true]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'stock_count' => 10,
        ]);

        $this->digitalKey = DigitalKey::create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'key_value' => 'ORIGINAL-KEY-VALUE-123',
            'key_hash' => hash('sha256', 'ORIGINAL-KEY-VALUE-123'),
            'status' => 'sold',
            'buyer_id' => null, // Will be set when sold
        ]);

        $this->buyer = User::factory()->create(['role' => 'buyer']);
        Wallet::create(['user_id' => $this->buyer->id, 'balance' => 0]);
        $this->token = $this->buyer->createToken('api-mobile')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ── License Listing Tests ───────────────────────────────────────────────

    public function test_user_can_list_their_licenses(): void
    {
        // Create order and sell key to buyer
        $order = Order::factory()->create([
            'buyer_id' => $this->buyer->id,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00,
        ]);
        $this->digitalKey->update([
            'buyer_id' => $this->buyer->id,
            'sold_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/licenses');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['ulid', 'product', 'key_value', 'status', 'sold_at'],
                ],
                'meta' => ['total', 'current_page', 'last_page'],
            ]);
    }

    public function test_user_only_sees_their_own_licenses(): void
    {
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $otherKey = DigitalKey::create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'key_value' => 'OTHER-KEY',
            'key_hash' => hash('sha256', 'OTHER-KEY'),
            'status' => 'sold',
            'buyer_id' => $otherBuyer->id,
            'sold_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/licenses');

        $response->assertJsonPath('meta.total', 0);
    }

    public function test_user_cannot_see_unsold_keys(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/licenses');

        $response->assertJsonPath('meta.total', 0);
    }

    // ── License Detail Tests ───────────────────────────────────────────────

    public function test_user_can_view_license_detail(): void
    {
        $this->digitalKey->update([
            'buyer_id' => $this->buyer->id,
            'sold_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/licenses/{$this->digitalKey->ulid}");

        $response->assertOk()
            ->assertJsonStructure([
                'ulid', 'product', 'key_value', 'status', 'sold_at', 'activation_guide',
            ]);
    }

    public function test_license_shows_activation_guide(): void
    {
        $this->product->update([
            'activation_guide' => '1. Go to activation.example.com\n2. Enter key',
        ]);
        $this->digitalKey->update([
            'buyer_id' => $this->buyer->id,
            'sold_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/licenses/{$this->digitalKey->ulid}");

        $response->assertOk()
            ->assertJsonPath('activation_guide', $this->product->activation_guide);
    }

    public function test_cannot_view_other_users_license(): void
    {
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $otherKey = DigitalKey::create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'key_value' => 'OTHER-KEY',
            'key_hash' => hash('sha256', 'OTHER-KEY'),
            'status' => 'sold',
            'buyer_id' => $otherBuyer->id,
            'sold_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/licenses/{$otherKey->ulid}");

        $response->assertNotFound();
    }

    // ── License Activation Tests ─────────────────────────────────────────

    public function test_user_can_activate_license(): void
    {
        $this->digitalKey->update([
            'buyer_id' => $this->buyer->id,
            'sold_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/licenses/{$this->digitalKey->ulid}/activate", [
                'machine_id' => 'MACHINE-001',
                'machine_name' => 'My PC',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'activation' => ['id', 'machine_id', 'machine_name', 'is_active'],
            ])
            ->assertJsonPath('activation.is_active', true);

        $this->assertDatabaseHas('license_activations', [
            'digital_key_id' => $this->digitalKey->id,
            'user_id' => $this->buyer->id,
            'machine_id' => 'MACHINE-001',
            'is_active' => true,
        ]);
    }

    public function test_activation_requires_machine_id(): void
    {
        $this->digitalKey->update(['buyer_id' => $this->buyer->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/licenses/{$this->digitalKey->ulid}/activate", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['machine_id']);
    }

    public function test_user_can_deactivate_license(): void
    {
        $this->digitalKey->update(['buyer_id' => $this->buyer->id]);
        LicenseActivation::create([
            'digital_key_id' => $this->digitalKey->id,
            'user_id' => $this->buyer->id,
            'machine_id' => 'MACHINE-001',
            'is_active' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/licenses/{$this->digitalKey->ulid}/deactivate", [
                'machine_id' => 'MACHINE-001',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Licencia desactivada en este dispositivo.');

        $this->assertDatabaseHas('license_activations', [
            'digital_key_id' => $this->digitalKey->id,
            'machine_id' => 'MACHINE-001',
            'is_active' => false,
        ]);
    }

    public function test_user_can_send_heartbeat(): void
    {
        $this->digitalKey->update(['buyer_id' => $this->buyer->id]);
        LicenseActivation::create([
            'digital_key_id' => $this->digitalKey->id,
            'user_id' => $this->buyer->id,
            'machine_id' => 'MACHINE-001',
            'is_active' => true,
            'last_seen_at' => now()->subHours(2),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/licenses/{$this->digitalKey->ulid}/heartbeat", [
                'machine_id' => 'MACHINE-001',
            ]);

        $response->assertOk()
            ->assertJsonPath('ok', true);

        $activation = LicenseActivation::where('machine_id', 'MACHINE-001')->first();
        $this->assertTrue($activation->last_seen_at->isAfter(now()->subMinute()));
    }

    // ── Authentication Tests ───────────────────────────────────────────────

    public function test_licenses_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/licenses');

        $response->assertStatus(401);
    }

    public function test_activation_requires_authentication(): void
    {
        $response = $this->postJson("/api/v1/licenses/{$this->digitalKey->ulid}/activate", [
            'machine_id' => 'TEST',
        ]);

        $response->assertStatus(401);
    }
}
