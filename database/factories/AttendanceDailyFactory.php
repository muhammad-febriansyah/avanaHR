<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceDaily;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceDaily>
 */
class AttendanceDailyFactory extends Factory
{
    protected $model = AttendanceDaily::class;

    public function definition(): array
    {
        $late = fake()->randomElement([0, 0, 0, 5, 15, 30]);

        return [
            'employee_id' => Employee::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'work_minutes' => fake()->numberBetween(420, 540),
            'late_minutes' => $late,
            'early_leave_minutes' => 0,
            'status' => $late > 0 ? AttendanceStatus::Late : AttendanceStatus::Present,
            'has_correction' => false,
        ];
    }

    public function absent(): static
    {
        return $this->state(fn (): array => [
            'work_minutes' => 0,
            'late_minutes' => 0,
            'status' => AttendanceStatus::Absent,
        ]);
    }
}
