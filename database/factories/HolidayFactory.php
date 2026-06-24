<?php

namespace Database\Factories;

use App\Models\Holiday;
use App\Models\WorkCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'calendar_id' => WorkCalendar::factory(),
            'date' => fake()->unique()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'name' => fake()->randomElement([
                'Tahun Baru', 'Hari Buruh', 'Hari Kemerdekaan', 'Idul Fitri',
                'Natal', 'Hari Pancasila', 'Waisak', 'Nyepi',
            ]),
            'is_national' => true,
        ];
    }
}
