<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'document_type' => fake()->randomElement(['ktp', 'npwp', 'contract', 'bpjs', 'passport']),
            'number' => fake()->numerify('################'),
            'issued_at' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'expired_at' => fake()->dateTimeBetween('+1 month', '+3 years')->format('Y-m-d'),
            'reminder_days' => 30,
            'access_level' => fake()->randomElement(['public', 'internal', 'confidential']),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expired_at' => now()->subDays(10)->format('Y-m-d'),
        ]);
    }

    public function expiring(): static
    {
        return $this->state(fn (): array => [
            'expired_at' => now()->addDays(10)->format('Y-m-d'),
        ]);
    }
}
