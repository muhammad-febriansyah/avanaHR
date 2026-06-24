<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        $entitled = fake()->numberBetween(6, 12);
        $used = fake()->numberBetween(0, $entitled);

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'year' => (int) fake()->year(),
            'entitled' => $entitled,
            'used' => $used,
            'pending' => 0,
            'expired' => 0,
            'available' => $entitled - $used,
        ];
    }
}
