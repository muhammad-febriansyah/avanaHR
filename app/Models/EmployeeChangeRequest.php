<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A maker-checker request to change an employee's master data. Holds the
 * proposed attribute diff until approved; applying it writes the payload onto
 * the employee. When no approval flow is configured for 'employee_change' the
 * engine auto-approves on submit, so the edit applies immediately.
 */
class EmployeeChangeRequest extends Model implements Approvable
{
    use BelongsToTenant, HasApprovals, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'before' => 'array',
            'status' => RequestStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approvalType(): string
    {
        return 'employee_change';
    }

    /**
     * @return array<string, mixed>
     */
    public function approvalContext(): array
    {
        $employment = $this->employee?->currentEmployment;

        return [
            'amount' => null,
            'department_id' => $employment?->department_id,
            'branch_id' => $employment?->branch_id,
            'fields' => array_keys($this->payload ?? []),
        ];
    }

    public function approvalTitle(): string
    {
        $name = $this->employee?->fullName() ?? 'Karyawan';
        $fields = implode(', ', array_keys($this->payload ?? []));

        return "Perubahan Data {$name}".($fields !== '' ? " — {$fields}" : '');
    }

    public function approvalRequesterUserId(): ?int
    {
        return $this->requested_by;
    }

    public function approvalRequesterEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function onApproved(): void
    {
        DB::transaction(function (): void {
            $this->employee?->update($this->payload ?? []);
            $this->update(['status' => RequestStatus::Approved]);
        });
    }

    public function onRejected(): void
    {
        $this->update(['status' => RequestStatus::Rejected]);
    }
}
