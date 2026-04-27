<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ────────────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@nexodigital.com'],
            [
                'name' => 'Nexo Admin',
                'password' => Hash::make('Admin@Nexo2025!'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Wallet NT para admin
        Wallet::firstOrCreate(
            ['user_id' => $admin->id],
            ['currency' => 'NT', 'balance' => 10000.00, 'locked_balance' => 0]
        );

        // Assign Spatie role
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command->info('✅ Admin creado: admin@nexodigital.com / Admin@Nexo2025!');

        // ── Demo Seller ────────────────────────────────────────────────────
        $seller = User::updateOrCreate(
            ['email' => 'seller@nexodigital.com'],
            [
                'name' => 'Demo Seller',
                'password' => Hash::make('Seller@Nexo2025!'),
                'role' => 'seller',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        Wallet::firstOrCreate(
            ['user_id' => $seller->id],
            ['currency' => 'NT', 'balance' => 500.00, 'locked_balance' => 0]
        );

        // Assign Spatie role
        if (! $seller->hasRole('seller')) {
            $seller->assignRole('seller');
        }

        $this->command->info('✅ Seller creado: seller@nexodigital.com / Seller@Nexo2025!');

        // ── Demo Buyer ─────────────────────────────────────────────────────
        $buyer = User::updateOrCreate(
            ['email' => 'buyer@nexodigital.com'],
            [
                'name' => 'Demo Buyer',
                'password' => Hash::make('Buyer@Nexo2025!'),
                'role' => 'buyer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        Wallet::firstOrCreate(
            ['user_id' => $buyer->id],
            ['currency' => 'NT', 'balance' => 150.00, 'locked_balance' => 0]
        );

        // Assign Spatie role
        if (! $buyer->hasRole('buyer')) {
            $buyer->assignRole('buyer');
        }

        $this->command->info('✅ Buyer creado: buyer@nexodigital.com / Buyer@Nexo2025!');
    }
}
