<?php

namespace Database\Factories;

use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TelegramUser>
 */
class TelegramUserFactory extends Factory
{
    protected $model = TelegramUser::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'telegram_id' => (string) fake()->unique()->numerify('#########'),
            'username' => fake()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'language_code' => 'es',
            'is_linked' => false,
            'preferred_currency' => 'USD',
            'state' => 'idle',
            'cart' => [],
            'notifications_enabled' => true,
            'last_interaction_at' => now(),
        ];
    }

    public function linked(?User $user = null): static
    {
        return $this->state(function () use ($user) {
            $user ??= User::factory()->create();

            return [
                'user_id' => $user->id,
                'is_linked' => true,
            ];
        });
    }
}
