<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);

        return [
            'employee_no' => 'EMP'.fake()->unique()->numerify('#####'),
            'first_name' => fake('id_ID')->firstName($gender),
            'last_name' => fake('id_ID')->lastName(),
            'gender' => $gender,
            'birth_date' => fake()->dateTimeBetween('-50 years', '-20 years'),
            'birth_place' => fake('id_ID')->city(),
            'religion' => fake()->randomElement(['islam', 'kristen', 'katolik', 'hindu', 'buddha']),
            'marital_status' => fake()->randomElement(['single', 'married']),
            'nik_ktp' => fake()->unique()->numerify('################'),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake('id_ID')->phoneNumber(),
            'address' => fake('id_ID')->address(),
            'status' => EmployeeStatus::Active,
            'join_date' => fake()->dateTimeBetween('-6 years', '-1 month'),
        ];
    }

    public function probation(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeStatus::Probation,
            'join_date' => fake()->dateTimeBetween('-2 months', 'now'),
        ]);
    }
}
