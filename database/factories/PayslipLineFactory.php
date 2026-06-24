<?php

namespace Database\Factories;

use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayslipLine>
 */
class PayslipLineFactory extends Factory
{
    protected $model = PayslipLine::class;

    public function definition(): array
    {
        return [
            'tenant_id' => app(CurrentTenant::class)->id(),
            'payslip_id' => Payslip::factory(),
            'component_code' => 'PC'.fake()->unique()->numberBetween(100, 999),
            'component_name' => fake()->randomElement(['Gaji Pokok', 'Tunjangan Transport', 'Potongan BPJS']),
            'type' => fake()->randomElement(['earning', 'deduction']),
            'amount' => fake()->numberBetween(100_000, 5_000_000),
        ];
    }
}
