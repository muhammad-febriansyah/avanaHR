<?php

namespace Database\Factories;

use App\Models\WorkCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkCalendar>
 */
class WorkCalendarFactory extends Factory
{
    protected $model = WorkCalendar::class;

    public function definition(): array
    {
        return [
            'name' => 'Kalender '.fake()->year(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['is_default' => true, 'name' => 'Kalender Nasional']);
    }
}
