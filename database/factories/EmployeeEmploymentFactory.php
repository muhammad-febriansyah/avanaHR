<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeEmployment>
 */
class EmployeeEmploymentFactory extends Factory
{
    protected $model = EmployeeEmployment::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'effective_date' => fake()->dateTimeBetween('-6 years', '-1 month'),
            'end_date' => null,
            'employment_type' => EmploymentType::Permanent,
            'status' => 'active',
        ];
    }
}
