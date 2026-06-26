<?php

namespace App\Models\Concerns;

use App\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Schema;

/**
 * Wires an Approvable model to its approval requests. The latest request is
 * also tracked via the optional `approval_request_id` column for quick lookup.
 */
trait HasApprovals
{
    /**
     * Full approval history for this transaction (polymorphic).
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    /**
     * The pending request currently routing this transaction, if any.
     */
    public function pendingApprovalRequest(): ?ApprovalRequest
    {
        return $this->approvalRequests()
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    /**
     * Point the optional `approval_request_id` column at the active request.
     */
    public function setActiveApprovalRequest(?int $requestId): void
    {
        if (Schema::hasColumn($this->getTable(), 'approval_request_id')) {
            $this->forceFill(['approval_request_id' => $requestId])->saveQuietly();
        }
    }
}
