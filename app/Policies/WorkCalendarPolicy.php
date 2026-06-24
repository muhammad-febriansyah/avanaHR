<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkCalendar;

class WorkCalendarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, WorkCalendar $workCalendar): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.create');
    }

    public function update(User $user, WorkCalendar $workCalendar): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, WorkCalendar $workCalendar): bool
    {
        return $user->can('employee.delete');
    }
}
