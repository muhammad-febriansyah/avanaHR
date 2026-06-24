<?php

namespace App\Policies;

use App\Models\PayrollComponent;
use App\Models\User;

class PayrollComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, PayrollComponent $payrollComponent): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.run');
    }

    public function update(User $user, PayrollComponent $payrollComponent): bool
    {
        return $user->can('payroll.run');
    }

    public function delete(User $user, PayrollComponent $payrollComponent): bool
    {
        return $user->can('payroll.run');
    }
}
