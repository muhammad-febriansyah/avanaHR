<?php

namespace Database\Factories;

use App\Enums\EmployeeMovementStatus;
use App\Enums\EmployeeMovementType;
use App\Models\Employee;
use App\Models\EmployeeMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeMovement>
 */
class EmployeeMovementFactory extends Factory
{
    protected $model = EmployeeMovement::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => fake()->randomElement(EmployeeMovementType::cases()),
            'effective_date' => fake()->dateTimeBetween('-1 year', '+1 month')->format('Y-m-d'),
            'status' => EmployeeMovementStatus::Draft,
            'payload_json' => [],
            'before_json' => null,
            'after_json' => null,
            'reason' => fake()->sentence(),
            'requires_clearance' => false,
        ];
    }

    public function type(EmployeeMovementType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function applied(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeMovementStatus::Applied,
            'applied_at' => now(),
        ]);
    }
}
