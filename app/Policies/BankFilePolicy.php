<?php

namespace App\Policies;

use App\Models\BankFile;
use App\Models\User;

class BankFilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, BankFile $bankFile): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.approve');
    }

    public function delete(User $user, BankFile $bankFile): bool
    {
        return $user->can('payroll.approve');
    }
}
