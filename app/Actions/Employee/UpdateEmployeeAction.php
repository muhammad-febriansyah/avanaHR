<?php

namespace App\Actions\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class UpdateEmployeeAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data): Employee {
            $employee->update($data);

            return $employee;
        });
    }
}
