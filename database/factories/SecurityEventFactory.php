<?php

namespace Database\Factories;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityEvent>
 */
class SecurityEventFactory extends Factory
{
    protected $model = SecurityEvent::class;

    public function definition(): array
    {
        $type = fake()->randomElement([
            'login_success', 'login_failed', 'locked_out', 'password_changed', 'two_factor_enabled',
        ]);

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'meta' => $type === 'login_failed' ? ['attempts' => fake()->numberBetween(1, 5)] : [],
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function type(string $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }
}
