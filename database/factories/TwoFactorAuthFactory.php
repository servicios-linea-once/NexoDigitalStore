<?php

namespace Database\Factories;

use App\Models\TwoFactorAuth;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TwoFactorAuthFactory extends Factory
{
    protected $model = TwoFactorAuth::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'secret' => 'JBSWY3DPEHPK3PXP',
            'recovery_codes' => ['CODEONE123', 'CODETWO456'],
            'is_enabled' => false,
            'enabled_at' => null,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn () => [
            'is_enabled' => true,
            'enabled_at' => now(),
        ]);
    }
}
