<?php

namespace App\Policies;

use App\Models\OvertimeRequest;
use App\Models\User;

class OvertimeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function view(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $user->can('attendance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attendance.manage');
    }

    public function update(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $user->can('attendance.manage');
    }

    public function delete(User $user, OvertimeRequest $overtimeRequest): bool
    {
        return $user->can('attendance.manage');
    }
}
