<?php

namespace Database\Factories;

use App\Models\JobLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobLevel>
 */
class JobLevelFactory extends Factory
{
    protected $model = JobLevel::class;

    public function definition(): array
    {
        return [
            'code' => 'LV'.fake()->unique()->numberBetween(1, 99),
            'name' => fake()->randomElement(['Staff', 'Senior', 'Supervisor', 'Manager', 'Director']),
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
