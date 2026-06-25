<?php

namespace App\Policies;

use App\Models\BenefitType;
use App\Models\User;

class BenefitTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll.view');
    }

    public function view(User $user, BenefitType $benefitType): bool
    {
        return $user->can('payroll.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll.approve');
    }

    public function update(User $user, BenefitType $benefitType): bool
    {
        return $user->can('payroll.approve');
    }

    public function delete(User $user, BenefitType $benefitType): bool
    {
        return $user->can('payroll.approve');
    }
}
