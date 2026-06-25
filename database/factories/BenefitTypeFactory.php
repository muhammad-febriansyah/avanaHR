<?php

namespace Database\Factories;

use App\Models\BenefitType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BenefitType>
 */
class BenefitTypeFactory extends Factory
{
    protected $model = BenefitType::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(6)),
            'name' => fake()->randomElement(['Medical', 'Kacamata', 'Asuransi', 'Gigi', 'Kesehatan']).' '.fake()->randomNumber(2),
            'default_quota' => fake()->numberBetween(1_000_000, 20_000_000),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
