<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    protected $model = OvertimeRequest::class;

    public function definition(): array
    {
        $date = Carbon::parse(fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'));
        $start = $date->copy()->setTime(18, 0);
        $end = $date->copy()->setTime(20, 0);

        return [
            'employee_id' => Employee::factory(),
            'date' => $date->format('Y-m-d'),
            'planned_start' => $start,
            'planned_end' => $end,
            'planned_minutes' => $start->diffInMinutes($end),
            'actual_minutes' => 0,
            'reason' => fake()->sentence(),
            'status' => RequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Approved]);
    }
}
