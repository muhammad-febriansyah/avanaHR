<?php

namespace App\Policies;

use App\Models\SalaryStructure;
use App\Models\User;

class SalaryStructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.run');
    }

    public function update(User $user, SalaryStructure $salaryStructure): bool
    {
        return $user->can('payroll.run');
    }

    public function delete(User $user, SalaryStructure $salaryStructure): bool
    {
        return $user->can('payroll.run');
    }
}
