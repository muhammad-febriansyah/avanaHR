<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkVisit;

class WorkVisitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, WorkVisit $workVisit): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.update');
    }

    public function update(User $user, WorkVisit $workVisit): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, WorkVisit $workVisit): bool
    {
        return $user->can('employee.update');
    }
}
