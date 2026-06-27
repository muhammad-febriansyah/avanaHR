<?php

namespace App\Http\Resources\Api;

use App\Models\AttendanceDaily;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AttendanceDaily
 */
class AttendanceDailyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->date?->toDateString(),
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'work_minutes' => (int) $this->work_minutes,
            'late_minutes' => (int) $this->late_minutes,
            'early_leave_minutes' => (int) $this->early_leave_minutes,
            'has_correction' => (bool) $this->has_correction,
            'clock_in' => $this->clock_in ?? null,
            'clock_out' => $this->clock_out ?? null,
        ];
    }
}
