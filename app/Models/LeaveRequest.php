<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model implements Approvable
{
    use BelongsToTenant, HasApprovals, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:2',
            'status' => RequestStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approvalType(): string
    {
        return 'leave';
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
            'leave_type_id' => $this->leave_type_id,
            'days' => (float) $this->days,
        ];
    }

    public function approvalTitle(): string
    {
        $name = $this->employee?->fullName() ?? 'Karyawan';

        return "Cuti {$name} — {$this->start_date?->format('d M Y')} s/d {$this->end_date?->format('d M Y')}";
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

        $this->deductBalance();
    }

    public function onRejected(): void
    {
        $this->update(['status' => RequestStatus::Rejected]);
    }

    /**
     * Deduct approved leave days from the matching balance: add to used and
     * recompute the available balance. No-op when no balance is configured.
     */
    private function deductBalance(): void
    {
        $balance = LeaveBalance::query()
            ->where('employee_id', $this->employee_id)
            ->where('leave_type_id', $this->leave_type_id)
            ->where('year', $this->start_date->year)
            ->first();

        if ($balance === null) {
            return;
        }

        $balance->used = (float) $balance->used + (float) $this->days;
        $balance->available = (float) $balance->entitled
            - (float) $balance->used
            - (float) $balance->pending
            - (float) $balance->expired;
        $balance->save();
    }
}
