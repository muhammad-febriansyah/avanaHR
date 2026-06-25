<?php

namespace Database\Factories;

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalFlowStep>
 */
class ApprovalFlowStepFactory extends Factory
{
    protected $model = ApprovalFlowStep::class;

    public function definition(): array
    {
        return [
            'flow_id' => ApprovalFlow::factory(),
            'order' => 1,
            'mode' => 'all',
            'approver_type' => fake()->randomElement(['manager', 'department_head', 'role']),
            'approver_ref' => null,
            'sla_hours' => 24,
            'escalate_to' => null,
            'allow_delegate' => false,
            'min_approvals' => 1,
        ];
    }
}
