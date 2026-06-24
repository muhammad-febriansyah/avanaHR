<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('employee.delete');
    }
}
