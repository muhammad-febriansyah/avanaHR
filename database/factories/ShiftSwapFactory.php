<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\ShiftSwap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftSwap>
 */
class ShiftSwapFactory extends Factory
{
    protected $model = ShiftSwap::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => Employee::factory(),
            'target_id' => Employee::factory(),
            'date_a' => now()->addWeek()->format('Y-m-d'),
            'date_b' => now()->addWeeks(2)->format('Y-m-d'),
            'status' => RequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Approved]);
    }
}
