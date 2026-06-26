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

class ShiftSwap extends Model implements Approvable
{
    use BelongsToTenant, HasApprovals, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_a' => 'date:Y-m-d',
            'date_b' => 'date:Y-m-d',
            'status' => RequestStatus::class,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'target_id');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approvalType(): string
    {
        return 'shift_swap';
    }

    /**
     * @return array<string, mixed>
     */
    public function approvalContext(): array
    {
        $employment = $this->requester?->currentEmployment;

        return [
            'amount' => null,
            'department_id' => $employment?->department_id,
            'branch_id' => $employment?->branch_id,
        ];
    }

    public function approvalTitle(): string
    {
        $requester = $this->requester?->fullName() ?? 'Karyawan';
        $target = $this->target?->fullName() ?? 'Karyawan';

        return "Tukar Shift {$requester} ↔ {$target}";
    }

    public function approvalRequesterUserId(): ?int
    {
        return $this->requester?->user?->id;
    }

    public function approvalRequesterEmployee(): ?Employee
    {
        return $this->requester;
    }

    /**
     * Exchange the shift assigned to the requester on date_a with the shift
     * assigned to the target on date_b. No-op if either roster entry is missing.
     */
    public function onApproved(): void
    {
        DB::transaction(function (): void {
            $a = Schedule::where('employee_id', $this->requester_id)->whereDate('date', $this->date_a)->first();
            $b = Schedule::where('employee_id', $this->target_id)->whereDate('date', $this->date_b)->first();

            if ($a !== null && $b !== null) {
                [$a->shift_id, $b->shift_id] = [$b->shift_id, $a->shift_id];
                $a->save();
                $b->save();
            }

            $this->update(['status' => RequestStatus::Approved]);
        });
    }

    public function onRejected(): void
    {
        $this->update(['status' => RequestStatus::Rejected]);
    }
}
