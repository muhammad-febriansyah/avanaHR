<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'));
        $end = $start->copy()->addDays(fake()->numberBetween(0, 4));

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days' => $start->diffInDays($end) + 1,
            'reason' => fake()->sentence(),
            'status' => RequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Approved]);
    }
}
