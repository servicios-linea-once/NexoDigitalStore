<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutamos seeders necesarios para que existan roles y settings
        Artisan::call('db:seed');
    }

    /**
     * Test public routes.
     */
    public function test_public_routes_are_accessible(): void
    {
        $routes = [
            '/',
            '/products',
            '/terms',
            '/login',
            '/register',
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertStatus(200, "Failed to load public route: {$url}");
        }
    }

    /**
     * Test protected routes redirect to login.
     */
    public function test_protected_routes_redirect_unauthenticated_users(): void
    {
        $routes = [
            '/profile',
            '/admin/dashboard',
            '/admin/users',
            '/cart',
            '/orders',
        ];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertRedirect('/login', "Route should be protected: {$url}");
        }
    }

    /**
     * Test admin routes as administrator.
     */
    public function test_admin_can_access_dashboard(): void
    {
        // Creamos un admin real con el rol de Spatie
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200, "Admin could not access dashboard");
    }

    /**
     * Test user profile as authenticated user.
     */
    public function test_user_can_access_profile(): void
    {
        $user = User::factory()->create(['role' => 'buyer', 'is_active' => true]);
        $user->assignRole('buyer');

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200, "User could not access profile");
    }
}
