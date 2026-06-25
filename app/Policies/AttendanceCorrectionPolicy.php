<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;

class AttendanceCorrectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance.view');
    }

    public function update(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance.manage');
    }

    public function delete(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->can('attendance.manage');
    }
}
