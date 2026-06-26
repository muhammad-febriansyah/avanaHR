<?php

namespace Database\Factories;

use App\Models\JobGrade;
use App\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryStructure>
 */
class SalaryStructureFactory extends Factory
{
    protected $model = SalaryStructure::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min = fake()->numberBetween(5_000_000, 15_000_000);

        return [
            'job_grade_id' => JobGrade::factory(),
            'band_min' => $min,
            'band_max' => $min + fake()->numberBetween(2_000_000, 10_000_000),
            'currency' => 'IDR',
        ];
    }
}
