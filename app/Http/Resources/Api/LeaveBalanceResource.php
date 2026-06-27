<?php

namespace App\Http\Resources\Api;

use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeaveBalance
 */
class LeaveBalanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'leave_type' => $this->whenLoaded('leaveType', fn () => $this->leaveType?->name),
            'year' => (int) $this->year,
            'entitled' => (float) $this->entitled,
            'used' => (float) $this->used,
            'pending' => (float) $this->pending,
            'expired' => (float) $this->expired,
            'available' => (float) $this->available,
        ];
    }
}
