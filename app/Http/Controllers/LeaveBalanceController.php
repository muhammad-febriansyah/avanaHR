<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveBalance\StoreLeaveBalanceRequest;
use App\Http\Requests\LeaveBalance\UpdateLeaveBalanceRequest;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveBalanceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LeaveBalance::class);

        $filters = $request->only('search', 'year', 'leave_type_id');
        $year = (int) ($filters['year'] ?? now()->year);

        $balances = LeaveBalance::query()
            ->with(['employee:id,first_name,last_name,employee_no', 'leaveType:id,name'])
            ->where('year', $year)
            ->when($filters['leave_type_id'] ?? null, fn ($query, $typeId) => $query->where('leave_type_id', $typeId))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (LeaveBalance $balance): array => [
                'id' => $balance->id,
                'employee_id' => $balance->employee_id,
                'employee_name' => $balance->employee?->fullName(),
                'employee_no' => $balance->employee?->employee_no,
                'leave_type_id' => $balance->leave_type_id,
                'leave_type_name' => $balance->leaveType?->name,
                'year' => (int) $balance->year,
                'entitled' => (float) $balance->entitled,
                'used' => (float) $balance->used,
                'pending' => (float) $balance->pending,
                'expired' => (float) $balance->expired,
                'available' => (float) $balance->available,
            ]);

        return Inertia::render('leave-balances/index', [
            'balances' => $balances,
            'filters' => (object) $filters,
            'currentYear' => $year,
            'options' => [
                'leaveTypes' => LeaveType::orderBy('name')->get(['id', 'name']),
                'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                    ->map(fn (Employee $employee): array => [
                        'id' => $employee->id,
                        'label' => $employee->fullName().' ('.$employee->employee_no.')',
                    ]),
                'years' => $this->yearOptions(),
            ],
        ]);
    }

    public function store(StoreLeaveBalanceRequest $request): RedirectResponse
    {
        LeaveBalance::create($this->withAvailable($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdateLeaveBalanceRequest $request, LeaveBalance $leaveBalance): RedirectResponse
    {
        $leaveBalance->update($this->withAvailable($request->validated()));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil diperbarui.']);

        return back();
    }

    public function destroy(LeaveBalance $leaveBalance): RedirectResponse
    {
        $this->authorize('delete', $leaveBalance);

        $leaveBalance->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil dihapus.']);

        return back();
    }

    /**
     * Derive the available balance from the entitled amount minus everything
     * already consumed, pending, or expired.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withAvailable(array $data): array
    {
        $data['available'] = (float) $data['entitled']
            - (float) $data['used']
            - (float) $data['pending']
            - (float) $data['expired'];

        return $data;
    }

    /**
     * @return list<int>
     */
    private function yearOptions(): array
    {
        $current = now()->year;

        return range($current + 1, $current - 3);
    }
}
