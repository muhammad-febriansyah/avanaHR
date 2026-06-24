<?php

namespace App\Policies;

use App\Models\CostCenter;
use App\Models\User;

class CostCenterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, CostCenter $costCenter): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.create');
    }

    public function update(User $user, CostCenter $costCenter): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, CostCenter $costCenter): bool
    {
        return $user->can('employee.delete');
    }
}
