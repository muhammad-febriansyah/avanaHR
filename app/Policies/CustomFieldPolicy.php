<?php

namespace App\Policies;

use App\Models\CustomField;
use App\Models\User;

class CustomFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('setting.manage');
    }

    public function view(User $user, CustomField $field): bool
    {
        return $user->can('setting.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('setting.manage');
    }

    public function update(User $user, CustomField $field): bool
    {
        return $user->can('setting.manage');
    }

    public function delete(User $user, CustomField $field): bool
    {
        return $user->can('setting.manage');
    }
}
