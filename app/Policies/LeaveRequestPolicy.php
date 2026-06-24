<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leave.view');
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave.view');
    }

    public function create(User $user): bool
    {
        return $user->can('leave.approve');
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave.approve');
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->can('leave.approve');
    }
}
