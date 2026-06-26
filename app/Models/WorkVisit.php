<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkVisit extends Model implements Approvable
{
    use BelongsToTenant, HasApprovals, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'estimated_cost' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(WorkVisitReport::class);
    }

    public function isPending(): bool
    {
        return $this->status === RequestStatus::Pending;
    }

    public function approvalType(): string
    {
        return 'work_visit';
    }

    /**
     * @return array<string, mixed>
     */
    public function approvalContext(): array
    {
        $employment = $this->employee?->currentEmployment;

        return [
            'amount' => $this->estimated_cost !== null ? (int) $this->estimated_cost : null,
            'destination' => $this->destination,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'department_id' => $employment?->department_id,
            'branch_id' => $employment?->branch_id,
        ];
    }

    public function approvalTitle(): string
    {
        $name = $this->employee?->fullName() ?? 'Karyawan';

        return "Kunjungan Kerja {$name} — {$this->destination}";
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
