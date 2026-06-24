<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\JobGrade;
use App\Models\JobLevel;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'job_level_id' => JobLevel::factory(),
            'job_grade_id' => JobGrade::factory(),
            'code' => 'POS'.fake()->unique()->numberBetween(100, 999),
            'name' => fake()->jobTitle(),
        ];
    }
}
