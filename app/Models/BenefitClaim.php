<?php

namespace App\Models;

use App\Approvals\ApprovalException;
use App\Contracts\Approvable;
use App\Enums\RequestStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BenefitClaim extends Model implements Approvable
{
    use BelongsToTenant, HasApprovals, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'claim_date' => 'date',
            'amount' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function employeeBenefit(): BelongsTo
    {
        return $this->belongsTo(EmployeeBenefit::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === RequestStatus::Pending;
    }

    public function approvalType(): string
    {
        return 'benefit_claim';
    }

    /**
     * @return array<string, mixed>
     */
    public function approvalContext(): array
    {
        $employment = $this->employeeBenefit?->employee?->currentEmployment;

        return [
            'amount' => (int) $this->amount,
            'department_id' => $employment?->department_id,
            'branch_id' => $employment?->branch_id,
            'claim_date' => $this->claim_date?->format('Y-m-d'),
        ];
    }

    public function approvalTitle(): string
    {
        $name = $this->employeeBenefit?->employee?->fullName() ?? 'Karyawan';
        $benefit = $this->employeeBenefit?->benefitType?->name ?? 'Benefit';

        return "Klaim {$benefit} {$name} — Rp ".number_format((int) $this->amount, 0, ',', '.');
    }

    public function approvalRequesterUserId(): ?int
    {
        return $this->employeeBenefit?->employee?->user?->id;
    }

    public function approvalRequesterEmployee(): ?Employee
    {
        return $this->employeeBenefit?->employee;
    }

    /**
     * Replicates the legacy decide() side-effect: enforce the benefit plafond
     * before approving. The engine runs this inside a DB transaction, so a
     * thrown exception rolls the approval back and the claim stays pending.
     */
    public function onApproved(): void
    {
        if ((int) $this->amount > $this->employeeBenefit->remainingQuota()) {
            throw new ApprovalException('Klaim melebihi sisa plafon');
        }

        $this->update(['status' => RequestStatus::Approved]);
    }

    public function onRejected(): void
    {
        $this->update(['status' => RequestStatus::Rejected]);
    }
}
