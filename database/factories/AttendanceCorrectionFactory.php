<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
{
    protected $model = AttendanceCorrection::class;

    public function definition(): array
    {
        $date = Carbon::parse(fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'));

        return [
            'employee_id' => Employee::factory(),
            'date' => $date->format('Y-m-d'),
            'requested_in' => $date->copy()->setTime(8, 0),
            'requested_out' => $date->copy()->setTime(17, 0),
            'reason' => fake()->sentence(),
            'status' => RequestStatus::Pending,
            'approval_request_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Approved]);
    }
}
