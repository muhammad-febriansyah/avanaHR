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
            // Cap below 900 so codes never collide with CMP900/CMP901 test literals.
            'code' => 'CMP'.fake()->unique()->numberBetween(100, 899),
            'name' => fake('id_ID')->company(),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'address' => fake('id_ID')->address(),
            'phone' => fake('id_ID')->phoneNumber(),
            'email' => fake()->companyEmail(),
        ];
    }
}
