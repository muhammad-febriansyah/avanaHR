<?php

namespace App\Policies;

use App\Models\AttendanceDaily;
use App\Models\User;

class AttendanceDailyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, AttendanceDaily $attendanceDaily): bool
    {
        return $user->can('attendance.view');
    }
}
