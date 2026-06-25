<?php

namespace App\Policies;

use App\Models\EmployeeDocument;
use App\Models\User;

class EmployeeDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, EmployeeDocument $document): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.update');
    }

    public function update(User $user, EmployeeDocument $document): bool
    {
        return $user->can('employee.update');
    }

    public function delete(User $user, EmployeeDocument $document): bool
    {
        return $user->can('employee.update');
    }
}
