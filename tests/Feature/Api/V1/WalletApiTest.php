<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Wallet $wallet;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'buyer']);
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'balance' => 1000.00,
            'locked_balance' => 100.00,
        ]);

        // Create free subscription
        $freePlan = SubscriptionPlan::factory()->create(['slug' => 'free']);
        UserSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $freePlan->id,
            'status' => 'active',
        ]);

        $this->token = $this->user->createToken('api-mobile')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    // ── Wallet Info Tests ───────────────────────────────────────────────────

    public function test_user_can_get_wallet_info(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/wallet');

        $response->assertOk()
            ->assertJsonStructure([
                'balance',
                'locked_balance',
                'available',
                'rate_usd',
                'balance_usd',
            ])
            ->assertJsonPath('balance', 1000.00)
            ->assertJsonPath('locked_balance', 100.00)
            ->assertJsonPath('available', 900.00);
    }

    public function test_wallet_shows_usd_conversion(): void
    {
        config(['nexo.token.rate_to_usd' => 0.10]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/wallet');

        $response->assertOk()
            ->assertJsonPath('balance_usd', 100.00)
            ->assertJsonPath('rate_usd', 0.10);
    }

    public function test_user_without_wallet_gets_zero_balance(): void
    {
        $userWithoutWallet = User::factory()->create();
        $token = $userWithoutWallet->createToken('api-mobile')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/v1/wallet');

        $response->assertOk()
            ->assertJsonPath('balance', 0)
            ->assertJsonPath('available', 0);
    }

    // ── Transaction History Tests ─────────────────────────────────────────

    public function test_user_can_get_transactions(): void
    {
        WalletTransaction::create([
            'wallet_id' => $this->wallet->id,
            'type' => 'credit',
            'amount' => 500,
            'balance_after' => 1500,
            'description' => 'Top-up test',
            'reference' => 'Topup:123',
        ]);
        WalletTransaction::create([
            'wallet_id' => $this->wallet->id,
            'type' => 'debit',
            'amount' => 100,
            'balance_after' => 1400,
            'description' => 'Purchase test',
            'reference' => 'Order:456',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/wallet/transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'type', 'amount', 'balance_after',
                        'description', 'reference', 'created_at',
                    ],
                ],
                'meta' => ['total', 'current_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_transactions_are_sorted_newest_first(): void
    {
        WalletTransaction::create([
            'wallet_id' => $this->wallet->id,
            'type' => 'credit',
            'amount' => 100,
            'balance_after' => 1100,
            'created_at' => now()->subDay(),
        ]);
        WalletTransaction::create([
            'wallet_id' => $this->wallet->id,
            'type' => 'credit',
            'amount' => 200,
            'balance_after' => 1300,
            'created_at' => now(),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/wallet/transactions');

        $response->assertJsonPath('data.0.amount', 200.00);
        $response->assertJsonPath('data.1.amount', 100.00);
    }

    public function test_transactions_are_paginated(): void
    {
        for ($i = 0; $i < 25; $i++) {
            WalletTransaction::create([
                'wallet_id' => $this->wallet->id,
                'type' => 'credit',
                'amount' => 10,
                'balance_after' => 1000 + ($i * 10),
            ]);
        }

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/wallet/transactions?page=2');

        $response->assertOk()
            ->assertJsonPath('meta.current_page', 2);
    }

    // ── Authentication Tests ───────────────────────────────────────────────

    public function test_wallet_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/wallet');

        $response->assertStatus(401);
    }

    public function test_transactions_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/wallet/transactions');

        $response->assertStatus(401);
    }
}
