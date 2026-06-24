<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'code' => 'CMP'.fake()->unique()->numberBetween(100, 999),
            'name' => fake('id_ID')->company(),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'address' => fake('id_ID')->address(),
            'phone' => fake('id_ID')->phoneNumber(),
            'email' => fake()->companyEmail(),
        ];
    }
}
