<?php

namespace App\Policies;

use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.run');
    }

    public function update(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->can('payroll.run');
    }

    public function delete(User $user, PayrollPeriod $payrollPeriod): bool
    {
        return $user->can('payroll.run');
    }
}
