<?php

namespace App\Policies;

use App\Models\EmployeeBenefit;
use App\Models\User;

class EmployeeBenefitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, EmployeeBenefit $employeeBenefit): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.approve');
    }

    public function update(User $user, EmployeeBenefit $employeeBenefit): bool
    {
        return $user->can('payroll.approve');
    }

    public function delete(User $user, EmployeeBenefit $employeeBenefit): bool
    {
        return $user->can('payroll.approve');
    }
}
