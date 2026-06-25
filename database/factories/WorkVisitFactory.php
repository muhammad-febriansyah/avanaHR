<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\WorkVisit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<WorkVisit>
 */
class WorkVisitFactory extends Factory
{
    protected $model = WorkVisit::class;

    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'));
        $end = $start->copy()->addDays(fake()->numberBetween(1, 4));

        return [
            'employee_id' => Employee::factory(),
            'destination' => fake('id_ID')->city(),
            'purpose' => fake()->sentence(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'transport_mode' => fake()->randomElement(['mobil', 'pesawat', 'kereta', 'bus', 'kapal', 'lainnya']),
            'estimated_cost' => fake()->numberBetween(500_000, 10_000_000),
            'status' => RequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => ['status' => RequestStatus::Rejected]);
    }
}
