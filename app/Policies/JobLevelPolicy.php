<?php

namespace App\Policies;

use App\Models\JobLevel;
use App\Models\User;

class JobLevelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, JobLevel $jobLevel): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.create');
    }

    public function update(User $user, JobLevel $jobLevel): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, JobLevel $jobLevel): bool
    {
        return $user->can('employee.delete');
    }
}
