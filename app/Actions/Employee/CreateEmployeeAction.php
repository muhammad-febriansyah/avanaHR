<?php

namespace App\Actions\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class CreateEmployeeAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Employee
    {
        return DB::transaction(fn (): Employee => Employee::create($data));
    }
}
