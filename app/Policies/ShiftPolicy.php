<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->can('attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.manage');
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->can('attendance.manage');
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->can('attendance.manage');
    }
}
