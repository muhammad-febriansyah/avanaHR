<?php

namespace App\Http\Controllers;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Requests\LeaveRequest\StoreLeaveRequestRequest;
use App\Http\Requests\LeaveRequest\UpdateLeaveRequestRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $filters = $request->only('search', 'status', 'leave_type_id');

        $requests = LeaveRequest::query()
            ->with(['employee:id,first_name,last_name,employee_no', 'leaveType:id,name'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['leave_type_id'] ?? null, fn ($query, $typeId) => $query->where('leave_type_id', $typeId))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (LeaveRequest $leave): array => [
                'id' => $leave->id,
                'employee_name' => $leave->employee?->fullName(),
                'employee_no' => $leave->employee?->employee_no,
                'leave_type_name' => $leave->leaveType?->name,
                'start_date' => $leave->start_date?->format('Y-m-d'),
                'end_date' => $leave->end_date?->format('Y-m-d'),
                'days' => (float) $leave->days,
                'reason' => $leave->reason,
                'status' => $leave->status->value,
            ]);

        return Inertia::render('leave-requests/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Pengajuan Cuti', 'href' => route('leave-requests.index')],
            ],
            'requests' => $requests,
            'filters' => (object) $filters,
            'statuses' => $this->statusOptions(),
            'options' => [
                'leaveTypes' => $this->leaveTypeOptions(),
                'employees' => $this->employeeOptions(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', LeaveRequest::class);

        return Inertia::render('leave-requests/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Pengajuan Cuti', 'href' => route('leave-requests.index')],
                ['title' => 'Ajukan', 'href' => route('leave-requests.create')],
            ],
            'leaveTypes' => $this->leaveTypeOptions(),
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $leave = LeaveRequest::create([
            ...$request->validated(),
            'days' => $request->requestedDays(),
            'status' => RequestStatus::Pending,
        ]);

        // Route through the approval engine: auto-approves when no flow is
        // configured, otherwise opens a request that approvers act on via the
        // approval inbox.
        $approval = $this->engine->submit($leave, $request->user());

        $message = $approval === null
            ? 'Pengajuan cuti dibuat dan disetujui otomatis (belum ada alur persetujuan).'
            : 'Pengajuan cuti diajukan dan menunggu persetujuan.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function edit(LeaveRequest $leaveRequest): Response|RedirectResponse
    {
        $this->authorize('update', $leaveRequest);

        if ($leaveRequest->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya pengajuan berstatus pending yang dapat diubah.']);

            return back();
        }

        $leaveRequest->loadMissing(['employee:id,first_name,last_name,employee_no', 'leaveType:id,name']);

        return Inertia::render('leave-requests/edit', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Pengajuan Cuti', 'href' => route('leave-requests.index')],
                ['title' => 'Edit', 'href' => route('leave-requests.edit', $leaveRequest)],
            ],
            'leaveRequest' => [
                'id' => $leaveRequest->id,
                'employee_name' => $leaveRequest->employee?->fullName(),
                'employee_no' => $leaveRequest->employee?->employee_no,
                'leave_type_name' => $leaveRequest->leaveType?->name,
                'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
                'reason' => $leaveRequest->reason,
            ],
            'balance' => $this->balanceInfo(
                $leaveRequest->employee_id,
                $leaveRequest->leave_type_id,
                $leaveRequest->start_date?->year,
            ),
        ]);
    }

    public function update(UpdateLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        if ($leaveRequest->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya pengajuan berstatus pending yang dapat diubah.']);

            return back();
        }

        $leaveRequest->update([
            ...$request->validated(),
            'days' => $request->requestedDays(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil diperbarui.']);

        return back();
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('delete', $leaveRequest);

        $leaveRequest->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil dihapus.']);

        return back();
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    private function leaveTypeOptions(): Collection
    {
        return LeaveType::orderBy('name')->get(['id', 'name'])
            ->map(fn (LeaveType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
            ]);
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
     * Resolve the configured leave balance for an employee + leave type + year,
     * returning null when none is configured.
     *
     * @return array{entitled: float, used: float, available: float}|null
     */
    private function balanceInfo(?int $employeeId, ?int $leaveTypeId, ?int $year): ?array
    {
        if ($employeeId === null || $leaveTypeId === null || $year === null) {
            return null;
        }

        $balance = LeaveBalance::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if ($balance === null) {
            return null;
        }

        return [
            'entitled' => (float) $balance->entitled,
            'used' => (float) $balance->used,
            'available' => (float) $balance->available,
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
