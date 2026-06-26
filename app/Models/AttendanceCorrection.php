<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\AttendanceStatus;
use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model implements Approvable
{
    use BelongsToTenant, HasApprovals, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'requested_in' => 'datetime',
            'requested_out' => 'datetime',
            'status' => RequestStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approvalType(): string
    {
        return 'attendance_correction';
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
            'date' => $this->date?->format('Y-m-d'),
        ];
    }

    public function approvalTitle(): string
    {
        $name = $this->employee?->fullName() ?? 'Karyawan';

        return "Koreksi Absensi {$name} — {$this->date?->format('d M Y')}";
    }

    public function approvalRequesterUserId(): ?int
    {
        return $this->employee?->user?->id;
    }

    public function approvalRequesterEmployee(): ?Employee
    {
        return $this->employee;
    }

    /**
     * Replicates the legacy decide() side-effect: raw attendance stays
     * immutable; the approved correction only flags the computed daily record
     * so payroll knows it was adjusted (RULE-003).
     */
    public function onApproved(): void
    {
        $this->update(['status' => RequestStatus::Approved]);

        $daily = AttendanceDaily::query()->firstOrNew([
            'employee_id' => $this->employee_id,
            'date' => $this->date->format('Y-m-d'),
        ]);

        $daily->fill([
            'work_minutes' => $daily->work_minutes ?? 0,
            'late_minutes' => $daily->late_minutes ?? 0,
            'early_leave_minutes' => $daily->early_leave_minutes ?? 0,
            'status' => $daily->status ?? AttendanceStatus::Present,
            'has_correction' => true,
        ])->save();
    }

    public function onRejected(): void
    {
        $this->update(['status' => RequestStatus::Rejected]);
    }
}
