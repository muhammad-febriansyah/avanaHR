<?php

namespace App\Policies;

use App\Models\ThrBonusRun;
use App\Models\User;

class ThrBonusRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, ThrBonusRun $thrBonusRun): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.run');
    }

    public function update(User $user, ThrBonusRun $thrBonusRun): bool
    {
        return $user->can('payroll.run');
    }

    public function delete(User $user, ThrBonusRun $thrBonusRun): bool
    {
        return $user->can('payroll.run');
    }
}
