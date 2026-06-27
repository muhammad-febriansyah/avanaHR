<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Attendance\RecordAttendanceClockAction;
use App\Http\Controllers\Api\Concerns\ResolvesEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockRequest;
use App\Http\Resources\Api\AttendanceDailyResource;
use App\Models\AttendanceDaily;
use App\Models\AttendanceLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    use ResolvesEmployee;

    public function clock(ClockRequest $request, RecordAttendanceClockAction $action): JsonResponse
    {
        $employee = $this->employee($request);

        $log = $action->execute($employee, $request->validated());

        return response()->json([
            'message' => $log->type === 'in' ? 'Clock-in tercatat.' : 'Clock-out tercatat.',
            'data' => [
                'type' => $log->type,
                'logged_at' => $log->logged_at?->toIso8601String(),
                'is_suspicious' => $log->is_suspicious,
                'today' => $this->todaySummary($employee->id),
            ],
        ], 201);
    }

    public function today(Request $request): JsonResponse
    {
        $employee = $this->employee($request);

        return response()->json(['data' => $this->todaySummary($employee->id)]);
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $this->employee($request);

        $daily = AttendanceDaily::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->paginate(31);

        $times = $this->clockTimes($employee->id, $daily->pluck('date')->all());

        $daily->getCollection()->transform(function (AttendanceDaily $row) use ($times): AttendanceDaily {
            $key = $row->date?->toDateString();
            $row->clock_in = $times[$key]['in'] ?? null;
            $row->clock_out = $times[$key]['out'] ?? null;

            return $row;
        });

        return AttendanceDailyResource::collection($daily)->response();
    }

    /**
     * @return array<string, mixed>
     */
    private function todaySummary(int $employeeId): array
    {
        $date = Carbon::now()->toDateString();

        $daily = AttendanceDaily::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();

        $times = $this->clockTimes($employeeId, [$date])[$date] ?? [];

        if ($daily) {
            $daily->clock_in = $times['in'] ?? null;
            $daily->clock_out = $times['out'] ?? null;
        }

        return [
            'date' => $date,
            'clock_in' => $times['in'] ?? null,
            'clock_out' => $times['out'] ?? null,
            'next_action' => isset($times['in']) ? 'out' : 'in',
            'summary' => $daily ? new AttendanceDailyResource($daily) : null,
        ];
    }

    /**
     * First clock-in and last clock-out (HH:mm) per date for one employee.
     *
     * @param  array<int, mixed>  $dates
     * @return array<string, array{in?: string, out?: string}>
     */
    private function clockTimes(int $employeeId, array $dates): array
    {
        $dates = array_values(array_filter(array_map(
            fn ($d): ?string => $d instanceof \DateTimeInterface ? Carbon::parse($d)->toDateString() : (is_string($d) ? $d : null),
            $dates,
        )));

        if ($dates === []) {
            return [];
        }

        $logs = AttendanceLog::query()
            ->where('employee_id', $employeeId)
            ->whereIn('type', ['in', 'out'])
            ->whereDate('logged_at', '>=', min($dates))
            ->whereDate('logged_at', '<=', max($dates))
            ->orderBy('logged_at')
            ->get(['type', 'logged_at']);

        $result = [];

        foreach ($logs as $log) {
            $key = $log->logged_at->toDateString();
            $time = $log->logged_at->format('H:i');

            if ($log->type === 'in') {
                $result[$key]['in'] ??= $time;
            } else {
                $result[$key]['out'] = $time;
            }
        }

        return $result;
    }
}
