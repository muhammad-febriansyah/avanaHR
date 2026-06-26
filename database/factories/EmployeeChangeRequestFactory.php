<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\EmployeeChangeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeChangeRequest>
 */
class EmployeeChangeRequestFactory extends Factory
{
    protected $model = EmployeeChangeRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'requested_by' => null,
            'payload' => ['phone' => fake()->phoneNumber()],
            'before' => ['phone' => fake()->phoneNumber()],
            'status' => RequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Approved]);
    }
}
