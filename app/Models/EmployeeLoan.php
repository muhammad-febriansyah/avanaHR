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

class EmployeeLoan extends Model implements Approvable
{
    use BelongsToTenant, HasApprovals, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'principal' => 'integer',
            'installment' => 'integer',
            'outstanding' => 'integer',
            'status' => RequestStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function startPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'start_period_id');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class, 'loan_id');
    }

    public function approvalType(): string
    {
        return 'loan';
    }

    /**
     * @return array<string, mixed>
     */
    public function approvalContext(): array
    {
        $employment = $this->employee?->currentEmployment;

        return [
            'amount' => (int) $this->principal,
            'tenor_months' => $this->tenor_months,
            'installment' => (int) $this->installment,
            'department_id' => $employment?->department_id,
            'branch_id' => $employment?->branch_id,
        ];
    }

    public function approvalTitle(): string
    {
        $name = $this->employee?->fullName() ?? 'Karyawan';
        $amount = number_format((float) $this->principal, 0, ',', '.');

        return "Pinjaman {$name} — Rp {$amount}";
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
