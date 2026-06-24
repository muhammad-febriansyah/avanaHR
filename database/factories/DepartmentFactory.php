<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'code' => 'DEP'.fake()->unique()->numberBetween(100, 999),
            'name' => fake()->randomElement([
                'Human Resources', 'Finance', 'Engineering', 'Sales',
                'Marketing', 'Operations', 'IT', 'Legal',
            ]),
        ];
    }
}
