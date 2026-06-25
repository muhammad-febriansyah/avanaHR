<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AttendanceLog>
 */
class AttendanceLogFactory extends Factory
{
    protected $model = AttendanceLog::class;

    public function definition(): array
    {
        $loggedAt = Carbon::parse(fake()->dateTimeBetween('-1 month', 'now'))->setTime(8, 0);

        return [
            'employee_id' => Employee::factory(),
            'type' => 'in',
            'logged_at' => $loggedAt,
            'source' => fake()->randomElement(['web', 'mobile', 'kiosk', 'biometric']),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'face_confidence' => fake()->randomFloat(3, 0.85, 0.999),
            'device_id' => fake()->bothify('DEV-####'),
            'offline_captured_at' => null,
            'is_suspicious' => false,
        ];
    }

    public function out(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'out',
            'logged_at' => Carbon::parse($attributes['logged_at'])->setTime(17, 0),
        ]);
    }
}
