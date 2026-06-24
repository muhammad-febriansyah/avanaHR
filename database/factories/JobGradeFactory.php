<?php

namespace Database\Factories;

use App\Models\JobGrade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobGrade>
 */
class JobGradeFactory extends Factory
{
    protected $model = JobGrade::class;

    public function definition(): array
    {
        $min = fake()->numberBetween(5, 30) * 1_000_000;

        return [
            'code' => 'GR'.fake()->unique()->numberBetween(1, 99),
            'name' => 'Grade '.fake()->randomLetter(),
            'salary_band_min' => $min,
            'salary_band_max' => $min + fake()->numberBetween(5, 20) * 1_000_000,
        ];
    }
}
