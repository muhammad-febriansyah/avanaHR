<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\Reimbursement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reimbursement>
 */
class ReimbursementFactory extends Factory
{
    protected $model = Reimbursement::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'category' => fake()->randomElement(['medical', 'transport', 'communication', 'entertainment', 'other']),
            'amount' => fake()->numberBetween(100_000, 5_000_000),
            'settlement' => fake()->randomElement(['payroll', 'cash', 'transfer']),
            'status' => RequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Approved]);
    }
}
