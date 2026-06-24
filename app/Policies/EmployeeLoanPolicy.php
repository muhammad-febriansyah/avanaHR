<?php

namespace App\Policies;

use App\Models\EmployeeLoan;
use App\Models\User;

class EmployeeLoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, EmployeeLoan $employeeLoan): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.run');
    }

    public function update(User $user, EmployeeLoan $employeeLoan): bool
    {
        return $user->can('payroll.run');
    }

    public function delete(User $user, EmployeeLoan $employeeLoan): bool
    {
        return $user->can('payroll.run');
    }
}
