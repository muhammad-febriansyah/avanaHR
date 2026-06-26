<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'shift_id' => Shift::factory(),
            'date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'status' => 'planned',
        ];
    }
}
