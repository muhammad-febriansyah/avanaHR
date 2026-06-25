<?php

namespace App\Actions\Employee;

use App\Actions\CustomField\SyncCustomFieldValuesAction;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class CreateEmployeeAction
{
    public function __construct(private readonly SyncCustomFieldValuesAction $syncCustomFields) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Employee
    {
        $customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        return DB::transaction(function () use ($data, $customFields): Employee {
            $employee = Employee::create($data);

            $this->syncCustomFields->handle($employee, 'employee', $customFields);

            return $employee;
        });
    }
}
