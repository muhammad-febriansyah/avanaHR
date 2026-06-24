<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeLoan>
 */
class EmployeeLoanFactory extends Factory
{
    protected $model = EmployeeLoan::class;

    public function definition(): array
    {
        $principal = fake()->numberBetween(1_000_000, 24_000_000);
        $tenor = fake()->numberBetween(3, 24);

        return [
            'employee_id' => Employee::factory(),
            'principal' => $principal,
            'tenor_months' => $tenor,
            'installment' => (int) ceil($principal / $tenor),
            'outstanding' => $principal,
            'status' => RequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Approved]);
    }
}
