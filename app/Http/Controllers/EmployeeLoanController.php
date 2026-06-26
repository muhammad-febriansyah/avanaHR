<?php

namespace App\Http\Controllers;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Requests\EmployeeLoan\StoreEmployeeLoanRequest;
use App\Http\Requests\EmployeeLoan\UpdateEmployeeLoanRequest;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeLoanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeLoan::class);

        $filters = $request->only('search', 'status');

        $loans = EmployeeLoan::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmployeeLoan $loan): array => [
                'id' => $loan->id,
                'employee_name' => $loan->employee?->fullName(),
                'employee_no' => $loan->employee?->employee_no,
                'principal' => (int) $loan->principal,
                'tenor_months' => $loan->tenor_months,
                'installment' => (int) $loan->installment,
                'outstanding' => (int) $loan->outstanding,
                'status' => $loan->status->value,
            ]);

        return Inertia::render('employee-loans/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Pinjaman', 'href' => route('employee-loans.index')],
            ],
            'loans' => $loans,
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

    public function store(StoreEmployeeLoanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $loan = EmployeeLoan::create([
            ...$data,
            'installment' => $this->monthlyInstallment($data['principal'], $data['tenor_months']),
            'outstanding' => $data['principal'],
            'status' => RequestStatus::Pending,
        ]);

        // Route through the approval engine: auto-approves when no flow is
        // configured, otherwise opens a request that approvers act on via the
        // approval inbox.
        $approval = $this->engine->submit($loan, $request->user());

        $message = $approval === null
            ? 'Pinjaman dibuat dan disetujui otomatis (belum ada alur persetujuan).'
            : 'Pinjaman diajukan dan menunggu persetujuan.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function update(UpdateEmployeeLoanRequest $request, EmployeeLoan $employeeLoan): RedirectResponse
    {
        if ($employeeLoan->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya pinjaman berstatus pending yang dapat diubah.']);

            return back();
        }

        $data = $request->validated();

        $employeeLoan->update([
            ...$data,
            'installment' => $this->monthlyInstallment($data['principal'], $data['tenor_months']),
            'outstanding' => $data['principal'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pinjaman berhasil diperbarui.']);

        return back();
    }

    public function destroy(EmployeeLoan $employeeLoan): RedirectResponse
    {
        $this->authorize('delete', $employeeLoan);

        $employeeLoan->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pinjaman berhasil dihapus.']);

        return back();
    }

    /**
     * Flat monthly installment, rounded up so the full principal is covered.
     */
    private function monthlyInstallment(int $principal, int $tenorMonths): int
    {
        return (int) ceil($principal / $tenorMonths);
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
