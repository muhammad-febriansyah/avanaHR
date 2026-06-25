<?php

namespace App\Policies;

use App\Models\HrTicket;
use App\Models\User;

class HrTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, HrTicket $ticket): bool
    {
        return $user->can('employee.view');
    }

    public function create(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function update(User $user, HrTicket $ticket): bool
    {
        return $user->can('employee.view');
    }
}
