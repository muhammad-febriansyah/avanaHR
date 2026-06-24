<?php

namespace Database\Factories;

use App\Models\PayrollComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollComponent>
 */
class PayrollComponentFactory extends Factory
{
    protected $model = PayrollComponent::class;

    public function definition(): array
    {
        return [
            'code' => 'PC'.fake()->unique()->numberBetween(100, 999),
            'name' => fake()->randomElement(['Gaji Pokok', 'Tunjangan Transport', 'Tunjangan Makan', 'Potongan Pinjaman']),
            'type' => fake()->randomElement(['earning', 'deduction']),
            'calc_type' => fake()->randomElement(['fixed', 'percentage', 'formula']),
            'formula' => null,
            'is_taxable' => true,
            'is_bpjs_base' => false,
        ];
    }
}
