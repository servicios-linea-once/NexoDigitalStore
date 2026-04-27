<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_generate_a_telegram_deep_link(): void
    {
        $this->withoutMiddleware(HandleInertiaRequests::class);
        $this->withoutMiddleware(EnsureUserIsActive::class);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('profile.telegram.token'));

        $response->assertOk()
            ->assertJsonStructure(['link', 'expires_in']);

        $user->refresh();

        $this->assertNotNull($user->telegram_link_token);
        $this->assertNotNull($user->telegram_link_token_expires_at);
        $this->assertStringContainsString($user->telegram_link_token, $response->json('link'));
        $this->assertSame(900, $response->json('expires_in'));
    }

    public function test_webhook_start_command_links_a_valid_token_and_clears_it(): void
    {
        Http::fake();

        $user = User::factory()->create([
            'telegram_link_token' => 'valid-token-123',
            'telegram_link_token_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->postJson(route('webhook.telegram'), [
            'message' => [
                'message_id' => 1,
                'text' => '/start vincular_valid-token-123',
                'chat' => ['id' => 555001],
                'from' => [
                    'id' => 555001,
                    'first_name' => 'Jhon',
                    'username' => 'jhon_bot',
                    'language_code' => 'es',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $user->refresh();
        $tgUser = TelegramUser::where('telegram_id', '555001')->firstOrFail();

        $this->assertSame($user->id, $tgUser->user_id);
        $this->assertTrue($tgUser->is_linked);
        $this->assertNull($user->telegram_link_token);
        $this->assertNull($user->telegram_link_token_expires_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event' => 'telegram_linked',
        ]);
    }

    public function test_webhook_rejects_an_expired_or_invalid_token_without_linking(): void
    {
        Http::fake();

        User::factory()->create([
            'telegram_link_token' => 'expired-token-123',
            'telegram_link_token_expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson(route('webhook.telegram'), [
            'message' => [
                'message_id' => 1,
                'text' => '/start vincular_expired-token-123',
                'chat' => ['id' => 555777],
                'from' => [
                    'id' => 555777,
                    'first_name' => 'Expired',
                    'username' => 'expired_bot',
                    'language_code' => 'es',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $tgUser = TelegramUser::where('telegram_id', '555777')->firstOrFail();

        $this->assertNull($tgUser->user_id);
        $this->assertFalse($tgUser->is_linked);
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'telegram_linked',
            'user_id' => $tgUser->user_id,
        ]);
    }
}
