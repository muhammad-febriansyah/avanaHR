<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.run');
    }

    public function update(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('payroll.run');
    }

    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        return $user->can('payroll.run');
    }
}
