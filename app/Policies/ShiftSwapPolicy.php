<?php

namespace App\Policies;

use App\Models\ShiftSwap;
use App\Models\User;

class ShiftSwapPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.manage');
    }

    public function delete(User $user, ShiftSwap $shiftSwap): bool
    {
        return $user->can('attendance.manage');
    }
}
