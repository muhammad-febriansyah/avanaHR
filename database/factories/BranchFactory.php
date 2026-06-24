<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'BR'.fake()->unique()->numberBetween(100, 999),
            'name' => 'Cabang '.fake('id_ID')->city(),
            'address' => fake('id_ID')->address(),
            'latitude' => fake()->latitude(-6.4, -6.1),   // Jakarta range
            'longitude' => fake()->longitude(106.7, 107.0),
            'radius_m' => fake()->randomElement([50, 100, 150, 200]),
        ];
    }
}
