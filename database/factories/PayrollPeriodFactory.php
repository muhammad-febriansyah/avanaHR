<?php

namespace Database\Factories;

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    public function definition(): array
    {
        $month = fake()->numberBetween(1, 12);
        $year = 2026;

        return [
            'code' => sprintf('PR-%d-%02d', $year, $month).fake()->unique()->numberBetween(1, 999),
            'month' => $month,
            'year' => $year,
            'cutoff_date' => sprintf('%d-%02d-25', $year, $month),
            'pay_date' => sprintf('%d-%02d-28', $year, $month),
            'status' => PayrollPeriodStatus::Draft,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => PayrollPeriodStatus::Approved]);
    }
}
