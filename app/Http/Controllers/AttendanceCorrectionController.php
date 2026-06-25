<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\RequestStatus;
use App\Http\Requests\AttendanceCorrection\DecideAttendanceCorrectionRequest;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceDaily;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceCorrectionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AttendanceCorrection::class);

        $filters = $request->only('search', 'status');

        $corrections = AttendanceCorrection::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (AttendanceCorrection $correction): array => [
                'id' => $correction->id,
                'employee_name' => $correction->employee?->fullName(),
                'employee_no' => $correction->employee?->employee_no,
                'date' => $correction->date?->format('Y-m-d'),
                'requested_in' => $correction->requested_in?->format('H:i'),
                'requested_out' => $correction->requested_out?->format('H:i'),
                'reason' => $correction->reason,
                'status' => $correction->status->value,
            ]);

        return Inertia::render('attendance-corrections/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Koreksi Absensi', 'href' => route('attendance-corrections.index')],
            ],
            'corrections' => $corrections,
            'filters' => (object) $filters,
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function decide(DecideAttendanceCorrectionRequest $request, AttendanceCorrection $attendanceCorrection): RedirectResponse
    {
        $this->authorize('update', $attendanceCorrection);

        if ($attendanceCorrection->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Koreksi ini sudah diproses.']);

            return back();
        }

        $status = RequestStatus::from($request->validated()['status']);
        $attendanceCorrection->update(['status' => $status]);

        // Raw attendance stays immutable; the approved correction only flags the
        // computed daily record so payroll knows it was adjusted (RULE-003).
        if ($status === RequestStatus::Approved) {
            $daily = AttendanceDaily::query()->firstOrNew([
                'employee_id' => $attendanceCorrection->employee_id,
                'date' => $attendanceCorrection->date->format('Y-m-d'),
            ]);

            $daily->fill([
                'work_minutes' => $daily->work_minutes ?? 0,
                'late_minutes' => $daily->late_minutes ?? 0,
                'early_leave_minutes' => $daily->early_leave_minutes ?? 0,
                'status' => $daily->status ?? AttendanceStatus::Present,
                'has_correction' => true,
            ])->save();
        }

        $label = $status === RequestStatus::Approved ? 'disetujui' : 'ditolak';
        Inertia::flash('toast', ['type' => 'success', 'message' => "Koreksi absensi {$label}."]);

        return back();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => RequestStatus::Pending->value, 'label' => 'Pending'],
            ['value' => RequestStatus::Approved->value, 'label' => 'Disetujui'],
            ['value' => RequestStatus::Rejected->value, 'label' => 'Ditolak'],
        ];
    }
}
