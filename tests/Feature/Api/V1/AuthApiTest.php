<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\TwoFactorAuth;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
    }

    // ── Login Tests ─────────────────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'expires_at',
                'user' => ['id', 'name', 'email', 'role', 'avatar', 'two_factor_enabled'],
            ])
            ->assertJsonPath('user.email', 'test@example.com');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_for_inactive_account(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Cuenta desactivada.');
    }

    public function test_login_requires_2fa_when_enabled(): void
    {
        TwoFactorAuth::factory()->enabled()->create([
            'user_id' => $this->user->id,
            'secret' => 'JBSWY3DPEHPK3PXP',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('requires_2fa', true)
            ->assertJsonStructure(['temp_token']);
    }

    public function test_2fa_verify_completes_login(): void
    {
        $twoFactorService = app(TwoFactorService::class);
        $record = $twoFactorService->generateSecret($this->user);
        $this->user->twoFactorAuth()->update(['is_enabled' => true]);

        // Get temp token first
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $tempToken = $loginResponse->json('temp_token');
        $otp = $twoFactorService->getCurrentOtp($record->secret);

        $response = $this->postJson('/api/v1/auth/2fa/verify', [
            'temp_token' => $tempToken,
            'otp' => $otp,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'two_factor_enabled'],
            ])
            ->assertJsonPath('user.two_factor_enabled', true);
    }

    public function test_2fa_verify_fails_with_invalid_otp(): void
    {
        TwoFactorAuth::factory()->enabled()->create([
            'user_id' => $this->user->id,
            'secret' => 'JBSWY3DPEHPK3PXP',
        ]);

        $tempToken = $this->user->createToken('2fa-pending')->plainTextToken;

        $response = $this->postJson('/api/v1/auth/2fa/verify', [
            'temp_token' => $tempToken,
            'otp' => '000000',
        ]);

        $response->assertStatus(401);
    }

    // ── Registration Tests ──────────────────────────────────────────────────

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_registration_requires_unique_email(): void
    {
        $this->user;

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ── Logout Tests ─────────────────────────────────────────────────────────

    public function test_user_can_logout(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada.');
    }

    public function test_user_can_logout_from_all_devices(): void
    {
        $this->user->createToken('device1')->plainTextToken;
        $this->user->createToken('device2')->plainTextToken;

        $token = $this->user->createToken('current')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout-all');

        $response->assertOk()
            ->assertJsonPath('message', 'Todas las sesiones han sido cerradas.');

        $this->assertEquals(0, $this->user->fresh()->tokens()->count());
    }

    public function test_user_can_refresh_token(): void
    {
        $token = $this->user->createToken('api-mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure(['token', 'expires_at']);
    }

    // ── 2FA Management Tests ───────────────────────────────────────────────

    public function test_user_can_enable_2fa(): void
    {
        $token = $this->user->createToken('api-mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/2fa/enable');

        $response->assertOk()
            ->assertJsonStructure(['secret', 'qr_svg', 'qr_base64', 'message']);

        $this->assertDatabaseHas('two_factor_auth', [
            'user_id' => $this->user->id,
            'is_enabled' => false,
        ]);
    }

    public function test_user_can_confirm_2fa_setup(): void
    {
        $twoFactorService = app(TwoFactorService::class);
        $record = $twoFactorService->generateSecret($this->user);
        $otp = $twoFactorService->getCurrentOtp($record->secret);

        $token = $this->user->createToken('api-mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/2fa/confirm', ['otp' => $otp]);

        $response->assertOk()
            ->assertJsonPath('enabled', true)
            ->assertJsonStructure(['recovery_codes']);
    }

    public function test_user_can_disable_2fa(): void
    {
        $twoFactorService = app(TwoFactorService::class);
        $record = $twoFactorService->generateSecret($this->user);
        $this->user->twoFactorAuth()->update(['is_enabled' => true]);
        $otp = $twoFactorService->getCurrentOtp($record->secret);

        $token = $this->user->createToken('api-mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/2fa/disable', ['otp' => $otp]);

        $response->assertOk();

        $this->assertDatabaseMissing('two_factor_auth', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_check_2fa_status(): void
    {
        $token = $this->user->createToken('api-mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/2fa/status');

        $response->assertOk()
            ->assertJsonStructure(['enabled', 'enabled_at']);
    }

    // ── Protected Routes Tests ──────────────────────────────────────────────

    public function test_protected_routes_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $token = $this->user->createToken('api-mobile')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/user');

        $response->assertOk()
            ->assertJsonPath('id', $this->user->id)
            ->assertJsonPath('email', 'test@example.com');
    }
}
