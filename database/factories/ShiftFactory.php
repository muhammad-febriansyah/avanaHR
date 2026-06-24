<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'code' => 'SH'.fake()->unique()->numberBetween(100, 999),
            'name' => fake()->randomElement(['Pagi', 'Siang', 'Malam', 'Normal']),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'break_min' => 60,
            'is_overnight' => false,
            'late_tolerance_min' => 15,
            'grace_min' => 5,
        ];
    }
}
