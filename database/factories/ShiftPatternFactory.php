<?php

namespace Database\Factories;

use App\Models\ShiftPattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftPattern>
 */
class ShiftPatternFactory extends Factory
{
    protected $model = ShiftPattern::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Rotasi 2-2', 'Mingguan', 'Pagi Tetap']),
            'type' => 'cyclic',
            'config' => ['days' => [null]],
        ];
    }
}
