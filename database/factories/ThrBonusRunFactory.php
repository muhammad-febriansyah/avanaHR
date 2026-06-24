<?php

namespace Database\Factories;

use App\Models\ThrBonusRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ThrBonusRun>
 */
class ThrBonusRunFactory extends Factory
{
    protected $model = ThrBonusRun::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['thr', 'bonus']),
            'period_ref' => fake()->randomElement(['Idul Fitri 2026', 'Natal 2026', 'Bonus Tahunan 2026']),
            'formula' => null,
            'status' => 'draft',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => 'approved']);
    }
}
