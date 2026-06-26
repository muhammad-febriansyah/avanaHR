<?php

namespace App\Actions\Employee;

use App\Actions\CustomField\SyncCustomFieldValuesAction;
use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\EmployeeChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Updates an employee through the maker-checker approval engine. Core master
 * data changes are routed as an {@see EmployeeChangeRequest}: with no
 * 'employee_change' flow configured the engine auto-approves and the change
 * applies immediately; with a flow it waits in the approval inbox. Custom
 * field values (supplementary data) are always applied immediately.
 */
class UpdateEmployeeAction
{
    public function __construct(
        private readonly SyncCustomFieldValuesAction $syncCustomFields,
        private readonly ApprovalEngine $engine,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{employee: Employee, pending: bool}
     */
    public function handle(Employee $employee, array $data, ?User $user = null): array
    {
        $customFields = $data['custom_fields'] ?? [];
        unset($data['custom_fields']);

        return DB::transaction(function () use ($employee, $data, $customFields, $user): array {
            $this->syncCustomFields->handle($employee, 'employee', $customFields);

            // Diff the proposed core attributes without persisting them yet.
            $employee->fill($data);
            $changes = $employee->getDirty();
            $before = [];
            foreach (array_keys($changes) as $key) {
                $before[$key] = $employee->getOriginal($key);
            }
            $employee->discardChanges();

            if ($changes === []) {
                return ['employee' => $employee, 'pending' => false];
            }

            $changeRequest = EmployeeChangeRequest::create([
                'tenant_id' => $employee->tenant_id,
                'employee_id' => $employee->id,
                'requested_by' => $user?->id,
                'payload' => $changes,
                'before' => $before,
                'status' => RequestStatus::Pending,
            ]);

            // No flow → auto-approved → onApproved() applies the change now.
            $approval = $this->engine->submit($changeRequest, $user);

            return ['employee' => $employee->fresh(), 'pending' => $approval !== null];
        });
    }
}
