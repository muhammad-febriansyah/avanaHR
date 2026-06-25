<?php

namespace Database\Factories;

use App\Models\BenefitType;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeBenefit>
 */
class EmployeeBenefitFactory extends Factory
{
    protected $model = EmployeeBenefit::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'benefit_type_id' => BenefitType::factory(),
            'period_year' => (int) now()->year,
            'quota' => fake()->numberBetween(1_000_000, 20_000_000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
