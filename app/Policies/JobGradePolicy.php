<?php

namespace App\Policies;

use App\Models\JobGrade;
use App\Models\User;

class JobGradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, JobGrade $jobGrade): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.create');
    }

    public function update(User $user, JobGrade $jobGrade): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, JobGrade $jobGrade): bool
    {
        return $user->can('employee.delete');
    }
}
