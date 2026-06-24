<?php

namespace App\Actions\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class DeleteEmployeeAction
{
    public function handle(Employee $employee): void
    {
        DB::transaction(fn () => $employee->delete());
    }
}
