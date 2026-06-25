<?php

namespace Database\Factories;

use App\Models\CostCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostCenter>
 */
class CostCenterFactory extends Factory
{
    protected $model = CostCenter::class;

    public function definition(): array
    {
        return [
            // Cap below 900 so codes never collide with CC900/CC901 test literals.
            'code' => 'CC'.fake()->unique()->numberBetween(100, 899),
            'name' => 'Cost Center '.fake()->randomElement(['HQ', 'Ops', 'Sales', 'RND']),
        ];
    }
}
