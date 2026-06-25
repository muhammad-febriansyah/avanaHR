<?php

namespace App\Policies;

use App\Models\Timesheet;
use App\Models\User;

class TimesheetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, Timesheet $timesheet): bool
    {
        return $user->can('attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.manage');
    }

    public function update(User $user, Timesheet $timesheet): bool
    {
        return $user->can('attendance.manage');
    }

    public function delete(User $user, Timesheet $timesheet): bool
    {
        return $user->can('attendance.manage');
    }
}
