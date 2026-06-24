<?php

namespace Database\Factories;

use App\Models\BankFile;
use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankFile>
 */
class BankFileFactory extends Factory
{
    protected $model = BankFile::class;

    public function definition(): array
    {
        $bank = fake()->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri', 'CIMB', 'Permata']);
        $format = fake()->randomElement(['csv', 'txt']);

        return [
            'run_id' => PayrollRun::factory(),
            'bank_code' => $bank,
            'format' => $format,
            'file_path' => 'bank-files/'.fake()->unique()->numerify('run-###').'-'.strtolower($bank).'.'.$format,
            'total' => fake()->numberBetween(10_000_000, 500_000_000),
            'exception_report_path' => null,
        ];
    }
}
