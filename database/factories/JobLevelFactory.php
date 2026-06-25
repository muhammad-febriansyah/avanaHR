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
            // Cap below 90 so codes never collide with LV90/LV91 test literals.
            'code' => 'LV'.fake()->unique()->numberBetween(1, 89),
            'name' => fake()->randomElement(['Staff', 'Senior', 'Supervisor', 'Manager', 'Director']),
            'order' => fake()->numberBetween(1, 5),
        ];
    }
}
