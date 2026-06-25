<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceDaily;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AttendanceDaily::class);

        $today = Carbon::today()->format('Y-m-d');
        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'from' => $request->date('from')?->format('Y-m-d') ?? $today,
            'to' => $request->date('to')?->format('Y-m-d') ?? $today,
        ];

        $daily = AttendanceDaily::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->whereDate('date', '>=', $filters['from'])
            ->whereDate('date', '<=', $filters['to'])
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'], fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('date')
            ->orderBy('employee_id')
            ->paginate(15)
            ->withQueryString();

        $clockTimes = $this->clockTimesFor($daily->getCollection());

        $daily->through(fn (AttendanceDaily $row): array => [
            'id' => $row->id,
            'employee_name' => $row->employee?->fullName(),
            'employee_no' => $row->employee?->employee_no,
            'date' => $row->date?->format('Y-m-d'),
            'clock_in' => $clockTimes[$row->employee_id.'|'.$row->date?->format('Y-m-d')]['in'] ?? null,
            'clock_out' => $clockTimes[$row->employee_id.'|'.$row->date?->format('Y-m-d')]['out'] ?? null,
            'work_minutes' => $row->work_minutes,
            'late_minutes' => $row->late_minutes,
            'status' => $row->status->value,
            'has_correction' => $row->has_correction,
        ]);

        return Inertia::render('attendance/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Rekap Absensi', 'href' => route('attendance.index')],
            ],
            'rows' => $daily,
            'filters' => (object) $filters,
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Resolve the first clock-in and last clock-out time per employee/date pair
     * for the visible page, derived from the immutable raw attendance logs.
     *
     * @param  Collection<int, AttendanceDaily>  $rows
     * @return array<string, array{in: ?string, out: ?string}>
     */
    private function clockTimesFor($rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $employeeIds = $rows->pluck('employee_id')->unique()->all();
        $dates = $rows->map(fn (AttendanceDaily $row): ?string => $row->date?->format('Y-m-d'))->unique()->all();

        $logs = AttendanceLog::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('type', ['in', 'out'])
            ->whereDate('logged_at', '>=', min($dates))
            ->whereDate('logged_at', '<=', max($dates))
            ->orderBy('logged_at')
            ->get(['employee_id', 'type', 'logged_at']);

        $result = [];

        foreach ($logs as $log) {
            $key = $log->employee_id.'|'.$log->logged_at->format('Y-m-d');
            $time = $log->logged_at->format('H:i');

            if ($log->type === 'in') {
                $result[$key]['in'] ??= $time;
            } else {
                $result[$key]['out'] = $time;
            }
        }

        return $result;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => AttendanceStatus::Present->value, 'label' => 'Hadir'],
            ['value' => AttendanceStatus::Late->value, 'label' => 'Terlambat'],
            ['value' => AttendanceStatus::Absent->value, 'label' => 'Absen'],
            ['value' => AttendanceStatus::Leave->value, 'label' => 'Cuti'],
            ['value' => AttendanceStatus::Holiday->value, 'label' => 'Libur'],
            ['value' => AttendanceStatus::Off->value, 'label' => 'Off'],
        ];
    }
}
