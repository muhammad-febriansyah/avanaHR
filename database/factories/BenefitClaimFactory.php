<?php

namespace Database\Factories;

use App\Enums\RequestStatus;
use App\Models\BenefitClaim;
use App\Models\EmployeeBenefit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BenefitClaim>
 */
class BenefitClaimFactory extends Factory
{
    protected $model = BenefitClaim::class;

    public function definition(): array
    {
        return [
            'employee_benefit_id' => EmployeeBenefit::factory(),
            'claim_date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'amount' => fake()->numberBetween(100_000, 2_000_000),
            'description' => fake()->sentence(),
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
