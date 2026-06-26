<?php

namespace App\Http\Controllers;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Requests\OvertimeRequest\StoreOvertimeRequestRequest;
use App\Http\Requests\OvertimeRequest\UpdateOvertimeRequestRequest;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class OvertimeRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ApprovalEngine $engine) {}

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
                'employees' => $this->employeeOptions(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', OvertimeRequest::class);

        return Inertia::render('overtime-requests/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Lembur', 'href' => route('overtime-requests.index')],
                ['title' => 'Ajukan', 'href' => route('overtime-requests.create')],
            ],
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(StoreOvertimeRequestRequest $request): RedirectResponse
    {
        $overtime = OvertimeRequest::create($this->fromValidated($request->validated()));

        // Route through the approval engine: auto-approves when no flow is
        // configured, otherwise opens a request that approvers act on via the
        // approval inbox.
        $approval = $this->engine->submit($overtime, $request->user());

        $message = $approval === null
            ? 'Pengajuan lembur dibuat dan disetujui otomatis (belum ada alur persetujuan).'
            : 'Pengajuan lembur diajukan dan menunggu persetujuan.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function edit(OvertimeRequest $overtimeRequest): Response|RedirectResponse
    {
        $this->authorize('update', $overtimeRequest);

        if ($overtimeRequest->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya pengajuan berstatus pending yang dapat diubah.']);

            return back();
        }

        return Inertia::render('overtime-requests/edit', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Lembur', 'href' => route('overtime-requests.index')],
                ['title' => 'Edit', 'href' => route('overtime-requests.edit', $overtimeRequest)],
            ],
            'overtimeRequest' => [
                'id' => $overtimeRequest->id,
                'employee_name' => $overtimeRequest->employee?->fullName(),
                'employee_no' => $overtimeRequest->employee?->employee_no,
                'date' => $overtimeRequest->date?->format('Y-m-d'),
                'start_time' => $overtimeRequest->planned_start?->format('H:i'),
                'end_time' => $overtimeRequest->planned_end?->format('H:i'),
                'reason' => $overtimeRequest->reason,
            ],
        ]);
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
     * @return Collection<int, array{id: int, label: string}>
     */
    private function employeeOptions(): Collection
    {
        return Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'label' => $employee->fullName().' ('.$employee->employee_no.')',
            ]);
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
