<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Línea Once Admin',
                'email' => 'admin@lineaonce.com',
                'username' => 'lineaonce',
                'role' => 'admin',
                'password' => Hash::make('Admin1234!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'AGX Digital Store',
                'email' => 'seller@agxdigital.com',
                'username' => 'agxdigital',
                'role' => 'seller',
                'password' => Hash::make('Seller1234!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Test Buyer',
                'email' => 'buyer@nexo.com',
                'username' => 'testbuyer',
                'role' => 'buyer',
                'password' => Hash::make('Buyer1234!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(['email' => $data['email']], $data);

            // Create NT wallet if missing
            if (! $user->wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'balance' => $user->role === 'admin' ? 10000 : 0,
                    'currency' => 'NT',
                ]);
            }
        }
    }
}
