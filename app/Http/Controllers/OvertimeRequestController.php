<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\OvertimeRequest\DecideOvertimeRequestRequest;
use App\Http\Requests\OvertimeRequest\StoreOvertimeRequestRequest;
use App\Http\Requests\OvertimeRequest\UpdateOvertimeRequestRequest;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeRequestController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OvertimeRequest::class);

        $filters = $request->only('search', 'status');

        $requests = OvertimeRequest::query()
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
            ->through(fn (OvertimeRequest $overtime): array => [
                'id' => $overtime->id,
                'employee_name' => $overtime->employee?->fullName(),
                'employee_no' => $overtime->employee?->employee_no,
                'date' => $overtime->date?->format('Y-m-d'),
                'start_time' => $overtime->planned_start?->format('H:i'),
                'end_time' => $overtime->planned_end?->format('H:i'),
                'planned_minutes' => $overtime->planned_minutes,
                'reason' => $overtime->reason,
                'status' => $overtime->status->value,
            ]);

        return Inertia::render('overtime-requests/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Lembur', 'href' => route('overtime-requests.index')],
            ],
            'requests' => $requests,
            'filters' => (object) $filters,
            'statuses' => $this->statusOptions(),
            'options' => [
                'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                    ->map(fn (Employee $employee): array => [
                        'id' => $employee->id,
                        'label' => $employee->fullName().' ('.$employee->employee_no.')',
                    ]),
            ],
        ]);
    }

    public function store(StoreOvertimeRequestRequest $request): RedirectResponse
    {
        OvertimeRequest::create($this->fromValidated($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan lembur berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateOvertimeRequestRequest $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        if ($overtimeRequest->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya pengajuan berstatus pending yang dapat diubah.']);

            return back();
        }

        $overtimeRequest->update($this->fromValidated($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan lembur berhasil diperbarui.']);

        return back();
    }

    public function decide(DecideOvertimeRequestRequest $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $this->authorize('update', $overtimeRequest);

        if ($overtimeRequest->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Pengajuan ini sudah diproses.']);

            return back();
        }

        $status = RequestStatus::from($request->validated()['status']);
        $overtimeRequest->update(['status' => $status]);

        $label = $status === RequestStatus::Approved ? 'disetujui' : 'ditolak';
        Inertia::flash('toast', ['type' => 'success', 'message' => "Pengajuan lembur {$label}."]);

        return back();
    }

    public function destroy(OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $this->authorize('delete', $overtimeRequest);

        $overtimeRequest->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan lembur berhasil dihapus.']);

        return back();
    }

    /**
     * Build the persisted columns from validated form input, deriving the
     * planned start/end datetimes and total minutes from the date + times.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function fromValidated(array $data): array
    {
        $start = Carbon::parse($data['date'].' '.$data['start_time']);
        $end = Carbon::parse($data['date'].' '.$data['end_time']);

        return [
            'employee_id' => $data['employee_id'] ?? null,
            'date' => $data['date'],
            'planned_start' => $start,
            'planned_end' => $end,
            'planned_minutes' => $start->diffInMinutes($end),
            'reason' => $data['reason'] ?? null,
        ];
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
