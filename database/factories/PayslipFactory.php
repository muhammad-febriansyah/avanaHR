<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payslip>
 */
class PayslipFactory extends Factory
{
    protected $model = Payslip::class;

    public function definition(): array
    {
        $gross = fake()->numberBetween(5_000_000, 20_000_000);
        $deductions = fake()->numberBetween(200_000, 1_500_000);

        return [
            'run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'snapshot' => null,
            'gross' => $gross,
            'deductions' => $deductions,
            'tax' => fake()->numberBetween(100_000, 1_000_000),
            'bpjs_employee' => fake()->numberBetween(50_000, 300_000),
            'bpjs_company' => fake()->numberBetween(100_000, 500_000),
            'net' => $gross - $deductions,
            'is_access_protected' => true,
        ];
    }
}
