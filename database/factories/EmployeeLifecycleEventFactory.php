<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeLifecycleEvent>
 */
class EmployeeLifecycleEventFactory extends Factory
{
    protected $model = EmployeeLifecycleEvent::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => fake()->randomElement(['hire', 'promotion', 'transfer', 'contract_change', 'resign']),
            'effective_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'from_json' => ['value' => fake()->jobTitle()],
            'to_json' => ['value' => fake()->jobTitle()],
            'reason' => fake()->sentence(),
        ];
    }

    public function type(string $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }
}
