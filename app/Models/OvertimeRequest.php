<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model implements Approvable
{
    use BelongsToTenant, HasApprovals, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'planned_start' => 'datetime',
            'planned_end' => 'datetime',
            'status' => RequestStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollComponent(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approvalType(): string
    {
        return 'overtime';
    }

    /**
     * @return array<string, mixed>
     */
    public function approvalContext(): array
    {
        $employment = $this->employee?->currentEmployment;

        return [
            'amount' => null,
            'minutes' => $this->planned_minutes,
            'department_id' => $employment?->department_id,
            'branch_id' => $employment?->branch_id,
            'date' => $this->date?->format('Y-m-d'),
        ];
    }

    public function approvalTitle(): string
    {
        $name = $this->employee?->fullName() ?? 'Karyawan';

        return "Lembur {$name} — {$this->date?->format('d M Y')} ({$this->planned_minutes} mnt)";
    }

    public function approvalRequesterUserId(): ?int
    {
        return $this->employee?->user?->id;
    }

    public function approvalRequesterEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function onApproved(): void
    {
        $this->update(['status' => RequestStatus::Approved]);
    }

    public function onRejected(): void
    {
        $this->update(['status' => RequestStatus::Rejected]);
    }
}
