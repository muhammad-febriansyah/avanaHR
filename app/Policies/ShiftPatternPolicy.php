<?php

namespace App\Policies;

use App\Models\ShiftPattern;
use App\Models\User;

class ShiftPatternPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.manage');
    }

    public function update(User $user, ShiftPattern $shiftPattern): bool
    {
        return $user->can('attendance.manage');
    }

    public function delete(User $user, ShiftPattern $shiftPattern): bool
    {
        return $user->can('attendance.manage');
    }
}
