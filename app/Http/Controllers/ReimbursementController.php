<?php

namespace App\Http\Controllers;

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Http\Requests\Reimbursement\StoreReimbursementRequest;
use App\Http\Requests\Reimbursement\UpdateReimbursementRequest;
use App\Models\Employee;
use App\Models\Reimbursement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReimbursementController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ApprovalEngine $engine) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Reimbursement::class);

        $filters = $request->only('search', 'status', 'category');

        $reimbursements = Reimbursement::query()
            ->with('employee:id,first_name,last_name,employee_no')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Reimbursement $reimbursement): array => [
                'id' => $reimbursement->id,
                'employee_name' => $reimbursement->employee?->fullName(),
                'employee_no' => $reimbursement->employee?->employee_no,
                'category' => $reimbursement->category,
                'amount' => (int) $reimbursement->amount,
                'settlement' => $reimbursement->settlement,
                'status' => $reimbursement->status->value,
            ]);

        return Inertia::render('reimbursements/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Reimbursement', 'href' => route('reimbursements.index')],
            ],
            'reimbursements' => $reimbursements,
            'filters' => (object) $filters,
            'statuses' => $this->statusOptions(),
            'categories' => $this->categoryOptions(),
            'settlements' => $this->settlementOptions(),
            'options' => [
                'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                    ->map(fn (Employee $employee): array => [
                        'id' => $employee->id,
                        'label' => $employee->fullName().' ('.$employee->employee_no.')',
                    ]),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('payroll.run'), 403);

        return Inertia::render('reimbursements/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Reimbursement', 'href' => route('reimbursements.index')],
                ['title' => 'Ajukan', 'href' => route('reimbursements.create')],
            ],
            'categories' => $this->categoryOptions(),
            'settlements' => $this->settlementOptions(),
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'employee_no'])
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'label' => $employee->fullName().' ('.$employee->employee_no.')',
                ]),
        ]);
    }

    public function store(StoreReimbursementRequest $request): RedirectResponse
    {
        $reimbursement = Reimbursement::create([
            ...$request->validated(),
            'status' => RequestStatus::Pending,
        ]);

        // Route through the approval engine: auto-approves when no flow is
        // configured, otherwise opens a request that approvers act on via the
        // approval inbox.
        $approval = $this->engine->submit($reimbursement, $request->user());

        $message = $approval === null
            ? 'Reimbursement dibuat dan disetujui otomatis (belum ada alur persetujuan).'
            : 'Reimbursement diajukan dan menunggu persetujuan.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return redirect()->route('reimbursements.index');
    }

    public function update(UpdateReimbursementRequest $request, Reimbursement $reimbursement): RedirectResponse
    {
        if ($reimbursement->status !== RequestStatus::Pending) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya reimbursement berstatus pending yang dapat diubah.']);

            return back();
        }

        $reimbursement->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reimbursement berhasil diperbarui.']);

        return back();
    }

    public function destroy(Reimbursement $reimbursement): RedirectResponse
    {
        $this->authorize('delete', $reimbursement);

        $reimbursement->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Reimbursement berhasil dihapus.']);

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

    /**
     * @return list<array{value: string, label: string}>
     */
    private function categoryOptions(): array
    {
        return [
            ['value' => 'medical', 'label' => 'Kesehatan'],
            ['value' => 'transport', 'label' => 'Transport'],
            ['value' => 'communication', 'label' => 'Komunikasi'],
            ['value' => 'entertainment', 'label' => 'Entertainment'],
            ['value' => 'other', 'label' => 'Lainnya'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function settlementOptions(): array
    {
        return [
            ['value' => 'payroll', 'label' => 'Via Payroll'],
            ['value' => 'cash', 'label' => 'Tunai'],
            ['value' => 'transfer', 'label' => 'Transfer'],
        ];
    }
}
