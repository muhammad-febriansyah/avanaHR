<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\LeaveRequest\DecideLeaveRequestRequest;
use App\Http\Requests\LeaveRequest\StoreLeaveRequestRequest;
use App\Http\Requests\LeaveRequest\UpdateLeaveRequestRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    use AuthorizesRequests;

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
                'leaveTypes' => LeaveType::orderBy('name')->get(['id', 'name']),
                'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                    ->map(fn (Employee $employee): array => [
                        'id' => $employee->id,
                        'label' => $employee->fullName().' ('.$employee->employee_no.')',
                    ]),
            ],
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        LeaveRequest::create([
            ...$request->validated(),
            'days' => $request->requestedDays(),
            'status' => RequestStatus::Pending,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil ditambahkan.']);

        return back();
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

    public function decide(DecideLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('update', $leaveRequest);

        if ($leaveRequest->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Pengajuan ini sudah diproses.']);

            return back();
        }

        $status = RequestStatus::from($request->validated()['status']);

        DB::transaction(function () use ($leaveRequest, $status): void {
            $leaveRequest->update(['status' => $status]);

            if ($status === RequestStatus::Approved) {
                $this->applyToBalance($leaveRequest);
            }
        });

        $label = $status === RequestStatus::Approved ? 'disetujui' : 'ditolak';
        Inertia::flash('toast', ['type' => 'success', 'message' => "Pengajuan cuti {$label}."]);

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
     * Deduct approved leave days from the matching balance: add to used and
     * recompute the available balance. No-op when no balance is configured.
     */
    private function applyToBalance(LeaveRequest $leaveRequest): void
    {
        $balance = LeaveBalance::query()
            ->where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $leaveRequest->start_date->year)
            ->first();

        if ($balance === null) {
            return;
        }

        $balance->used = (float) $balance->used + (float) $leaveRequest->days;
        $balance->available = (float) $balance->entitled
            - (float) $balance->used
            - (float) $balance->pending
            - (float) $balance->expired;
        $balance->save();
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
