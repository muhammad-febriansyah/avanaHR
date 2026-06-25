<?php

namespace App\Models;

use App\Enums\EmployeeMovementStatus;
use App\Enums\EmployeeMovementType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeMovement extends Model
{
    use BelongsToTenant, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => EmployeeMovementType::class,
            'status' => EmployeeMovementStatus::class,
            'effective_date' => 'date',
            'payload_json' => 'array',
            'before_json' => 'array',
            'after_json' => 'array',
            'requires_clearance' => 'boolean',
            'applied_at' => 'datetime',
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

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function clearanceItems(): HasMany
    {
        return $this->hasMany(ClearanceItem::class);
    }

    /**
     * Draft (apply now) or scheduled (auto-apply on date) movements can be applied.
     */
    public function canApply(): bool
    {
        return in_array($this->status, [EmployeeMovementStatus::Draft, EmployeeMovementStatus::Scheduled], true);
    }

    /**
     * Only a draft can be queued for effective-date auto-apply.
     */
    public function canSchedule(): bool
    {
        return $this->status === EmployeeMovementStatus::Draft;
    }

    public function canUnschedule(): bool
    {
        return $this->status === EmployeeMovementStatus::Scheduled;
    }

    /**
     * Exit movements are blocked from applying while clearance items remain pending.
     */
    public function hasPendingClearance(): bool
    {
        if (! $this->requires_clearance) {
            return false;
        }

        return $this->clearanceItems()->where('status', 'pending')->exists();
    }

    /**
     * Whether the movement may be applied right now (draft + clearance cleared).
     */
    public function isApplicable(): bool
    {
        return $this->canApply() && ! $this->hasPendingClearance();
    }
}
