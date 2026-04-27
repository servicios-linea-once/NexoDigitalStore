<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => fake()->randomFloat(4, 0, 500),
            'locked_balance' => fake()->randomFloat(4, 0, 50),
            'currency' => 'NT',
            'signature' => null,
        ];
    }
}
