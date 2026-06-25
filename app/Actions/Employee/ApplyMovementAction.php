<?php

namespace App\Actions\Employee;

use App\Enums\EmployeeMovementStatus;
use App\Enums\EmployeeMovementType;
use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeLifecycleEvent;
use App\Models\EmployeeMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplyMovementAction
{
    public function execute(EmployeeMovement $movement, ?User $actor = null): void
    {
        if (! $movement->isApplicable()) {
            throw new \RuntimeException('Movement is not applicable.');
        }

        DB::transaction(function () use ($movement, $actor): void {
            $employee = $movement->employee()->firstOrFail();
            $current = $employee->currentEmployment()->first();

            $beforeJson = $this->snapshot($current);

            $payload = $movement->payload_json ?? [];

            $attributes = [
                'company_id' => $current?->company_id,
                'branch_id' => $current?->branch_id,
                'department_id' => $current?->department_id,
                'position_id' => $current?->position_id,
                'job_grade_id' => $current?->job_grade_id,
                'cost_center_id' => $current?->cost_center_id,
                'manager_id' => $current?->manager_id,
                'work_calendar_id' => $current?->work_calendar_id,
                'employment_type' => $current?->employment_type ?? 'permanent',
            ];

            foreach (['position_id', 'department_id', 'job_grade_id', 'manager_id', 'employment_type'] as $field) {
                if (($payload[$field] ?? null) !== null) {
                    $attributes[$field] = $payload[$field];
                }
            }

            $attributes['employee_id'] = $employee->id;
            $attributes['effective_date'] = $movement->effective_date;
            $attributes['end_date'] = null;
            $attributes['status'] = $this->employmentStatusFor($movement->type);

            if ($current !== null) {
                $current->update(['end_date' => $movement->effective_date]);
            }

            $newEmployment = EmployeeEmployment::create($attributes);

            $employee->update($this->employeeUpdatesFor($movement->type, $movement->effective_date));

            $this->propagateAccess($employee, $movement->type);

            $afterJson = $this->snapshot($newEmployment);

            $movement->update([
                'status' => EmployeeMovementStatus::Applied,
                'applied_at' => now(),
                'applied_by' => $actor?->id,
                'before_json' => $beforeJson,
                'after_json' => $afterJson,
            ]);

            EmployeeLifecycleEvent::create([
                'employee_id' => $employee->id,
                'type' => $movement->type->value,
                'effective_date' => $movement->effective_date,
                'from_json' => $beforeJson,
                'to_json' => $afterJson,
                'reason' => $movement->reason,
            ]);
        });
    }

    /**
     * Propagate the movement to the employee's login user so web access
     * follows their employment status (revoke on exit/suspend, restore on reactivate).
     */
    private function propagateAccess(Employee $employee, EmployeeMovementType $type): void
    {
        $user = $employee->user;

        if ($user === null) {
            return;
        }

        $status = match ($type) {
            EmployeeMovementType::Resign,
            EmployeeMovementType::Terminate,
            EmployeeMovementType::Suspension => 'inactive',
            EmployeeMovementType::Reactivate => 'active',
            default => null,
        };

        if ($status !== null) {
            $user->update(['status' => $status]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function snapshot(?EmployeeEmployment $employment): ?array
    {
        if ($employment === null) {
            return null;
        }

        return [
            'position_id' => $employment->position_id,
            'department_id' => $employment->department_id,
            'job_grade_id' => $employment->job_grade_id,
            'manager_id' => $employment->manager_id,
            'cost_center_id' => $employment->cost_center_id,
            'employment_type' => $employment->employment_type instanceof \BackedEnum
                ? $employment->employment_type->value
                : $employment->employment_type,
            'status' => $employment->status,
        ];
    }

    private function employmentStatusFor(EmployeeMovementType $type): string
    {
        return match ($type) {
            EmployeeMovementType::Resign => 'inactive',
            EmployeeMovementType::Terminate => 'terminated',
            EmployeeMovementType::Suspension => 'suspended',
            default => 'active',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeUpdatesFor(EmployeeMovementType $type, mixed $effectiveDate): array
    {
        return match ($type) {
            EmployeeMovementType::Resign => ['status' => EmployeeStatus::Resigned, 'resign_date' => $effectiveDate],
            EmployeeMovementType::Terminate => ['status' => EmployeeStatus::Terminated, 'resign_date' => $effectiveDate],
            EmployeeMovementType::Suspension => ['status' => EmployeeStatus::Suspended],
            default => ['status' => EmployeeStatus::Active],
        };
    }
}
