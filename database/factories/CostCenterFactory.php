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
            'code' => 'CC'.fake()->unique()->numberBetween(100, 999),
            'name' => 'Cost Center '.fake()->randomElement(['HQ', 'Ops', 'Sales', 'RND']),
        ];
    }
}
