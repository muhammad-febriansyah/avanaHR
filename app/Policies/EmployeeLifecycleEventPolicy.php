<?php

namespace App\Policies;

use App\Models\EmployeeLifecycleEvent;
use App\Models\User;

class EmployeeLifecycleEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, EmployeeLifecycleEvent $event): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, EmployeeLifecycleEvent $event): bool
    {
        return $user->can('employee.update');
    }
}
