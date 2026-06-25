<?php

namespace App\Policies;

use App\Models\EmployeeMovement;
use App\Models\User;

class EmployeeMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, EmployeeMovement $movement): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.update');
    }

    public function apply(User $user, EmployeeMovement $movement): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, EmployeeMovement $movement): bool
    {
        return $user->can('employee.update');
    }
}
