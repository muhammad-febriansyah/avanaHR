<?php

namespace App\Policies;

use App\Models\LeaveBalance;
use App\Models\User;

class LeaveBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leave.view');
    }

    public function view(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->can('leave.view');
    }

    public function create(User $user): bool
    {
        return $user->can('leave.approve');
    }

    public function update(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->can('leave.approve');
    }

    public function delete(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->can('leave.approve');
    }
}
