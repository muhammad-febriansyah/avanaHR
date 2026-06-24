<?php

namespace App\Policies;

use App\Models\Reimbursement;
use App\Models\User;

class ReimbursementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, Reimbursement $reimbursement): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.run');
    }

    public function update(User $user, Reimbursement $reimbursement): bool
    {
        return $user->can('payroll.run');
    }

    public function delete(User $user, Reimbursement $reimbursement): bool
    {
        return $user->can('payroll.run');
    }
}
