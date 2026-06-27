<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceDaily;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Records a mobile clock-in / clock-out as an immutable raw {@see AttendanceLog}
 * and (re)derives the daily summary for that employee + date. Raw logs are never
 * mutated; corrections happen through the separate approval flow.
 */
class RecordAttendanceClockAction
{
    /** Minimum face-match confidence before a punch is flagged suspicious. */
    private const FACE_THRESHOLD = 0.85;

    /**
     * @param  array{type: string, latitude?: float|null, longitude?: float|null, face_confidence?: float|null, device_id?: string|null, captured_at?: string|null}  $data
     */
    public function execute(Employee $employee, array $data): AttendanceLog
    {
        return DB::transaction(function () use ($employee, $data): AttendanceLog {
            $capturedAt = ! empty($data['captured_at']) ? Carbon::parse($data['captured_at']) : null;
            $loggedAt = $capturedAt ?? Carbon::now();
            $confidence = $data['face_confidence'] ?? null;

            $log = AttendanceLog::create([
                'employee_id' => $employee->id,
                'type' => $data['type'],
                'logged_at' => $loggedAt,
                'source' => 'mobile',
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'face_confidence' => $confidence,
                'device_id' => $data['device_id'] ?? null,
                'offline_captured_at' => $capturedAt,
                'is_suspicious' => $confidence !== null && (float) $confidence < self::FACE_THRESHOLD,
            ]);

            $this->recomputeDaily($employee, $loggedAt->toDateString());

            return $log;
        });
    }

    /**
     * Rebuild the daily summary from the immutable logs for one employee + date.
     */
    private function recomputeDaily(Employee $employee, string $date): void
    {
        $logs = AttendanceLog::query()
            ->where('employee_id', $employee->id)
            ->whereDate('logged_at', $date)
            ->whereIn('type', ['in', 'out'])
            ->orderBy('logged_at')
            ->get();

        $firstIn = $logs->firstWhere('type', 'in')?->logged_at;
        $lastOut = $logs->where('type', 'out')->last()?->logged_at;

        $shift = Schedule::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->with('shift')
            ->first()?->shift;

        $lateMinutes = 0;
        $earlyLeaveMinutes = 0;
        $workMinutes = 0;

        if ($firstIn && $lastOut) {
            $workMinutes = (int) $firstIn->diffInMinutes($lastOut);
            if ($shift && $shift->break_min) {
                $workMinutes = max(0, $workMinutes - (int) $shift->break_min);
            }
        }

        if ($shift && $firstIn) {
            $shiftStart = Carbon::parse($date.' '.$shift->start_time)
                ->addMinutes((int) ($shift->grace_min ?? 0));
            if ($firstIn->greaterThan($shiftStart)) {
                $lateMinutes = (int) $shiftStart->diffInMinutes($firstIn);
            }
        }

        if ($shift && $lastOut) {
            $shiftEnd = Carbon::parse($date.' '.$shift->end_time);
            if ($lastOut->lessThan($shiftEnd)) {
                $earlyLeaveMinutes = (int) $lastOut->diffInMinutes($shiftEnd);
            }
        }

        $status = $lateMinutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present;

        AttendanceDaily::query()->updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date],
            [
                'work_minutes' => $workMinutes,
                'late_minutes' => $lateMinutes,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'status' => $status->value,
            ],
        );
    }
}
