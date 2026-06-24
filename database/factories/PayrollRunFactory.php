<?php

namespace Database\Factories;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollRun>
 */
class PayrollRunFactory extends Factory
{
    protected $model = PayrollRun::class;

    public function definition(): array
    {
        return [
            'period_id' => PayrollPeriod::factory(),
            'run_no' => 'RUN-'.fake()->unique()->numerify('######'),
            'type' => 'regular',
            'status' => 'draft',
            'gross_total' => 0,
            'net_total' => 0,
            'tax_total' => 0,
            'bpjs_total' => 0,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => 'approved']);
    }
}
