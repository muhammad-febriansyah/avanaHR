<?php

namespace Database\Factories;

use App\Models\ClearanceItem;
use App\Models\EmployeeMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClearanceItem>
 */
class ClearanceItemFactory extends Factory
{
    protected $model = ClearanceItem::class;

    public function definition(): array
    {
        return [
            'employee_movement_id' => EmployeeMovement::factory(),
            'category' => fake()->randomElement(['hr', 'finance', 'it', 'asset', 'legal']),
            'label' => fake()->sentence(),
            'status' => 'pending',
        ];
    }

    public function done(): static
    {
        return $this->state(fn (): array => [
            'status' => 'done',
            'completed_at' => now(),
        ]);
    }

    public function waived(): static
    {
        return $this->state(fn (): array => [
            'status' => 'waived',
            'completed_at' => now(),
        ]);
    }
}
