<?php

namespace Database\Factories;

use App\Models\ApprovalFlow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalFlow>
 */
class ApprovalFlowFactory extends Factory
{
    protected $model = ApprovalFlow::class;

    public function definition(): array
    {
        return [
            'transaction_type' => fake()->randomElement(['leave', 'overtime', 'reimbursement', 'loan']),
            'name' => 'Alur '.fake()->words(2, true),
            'is_active' => true,
            'conditions' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
