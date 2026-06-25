<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Requests\EmployeeBenefit\DecideBenefitClaimRequest;
use App\Http\Requests\EmployeeBenefit\StoreBenefitClaimRequest;
use App\Http\Requests\EmployeeBenefit\StoreEmployeeBenefitRequest;
use App\Models\BenefitClaim;
use App\Models\BenefitType;
use App\Models\Employee;
use App\Models\EmployeeBenefit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeBenefitController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeBenefit::class);

        $filters = $request->only('search', 'period_year');

        $employeeBenefits = EmployeeBenefit::query()
            ->with(['employee:id,first_name,last_name,employee_no', 'benefitType:id,name'])
            ->when($filters['period_year'] ?? null, fn ($query, $year) => $query->where('period_year', $year))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('period_year')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (EmployeeBenefit $employeeBenefit): array => [
                'id' => $employeeBenefit->id,
                'employee_name' => $employeeBenefit->employee?->fullName(),
                'employee_no' => $employeeBenefit->employee?->employee_no,
                'benefit_type_name' => $employeeBenefit->benefitType?->name,
                'period_year' => $employeeBenefit->period_year,
                'quota' => (int) $employeeBenefit->quota,
                'used' => $employeeBenefit->usedAmount(),
                'remaining' => $employeeBenefit->remainingQuota(),
            ]);

        return Inertia::render('employee-benefits/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Benefit Karyawan', 'href' => route('employee-benefits.index')],
            ],
            'employeeBenefits' => $employeeBenefits,
            'filters' => (object) $filters,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EmployeeBenefit::class);

        return Inertia::render('employee-benefits/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Benefit Karyawan', 'href' => route('employee-benefits.index')],
                ['title' => 'Tetapkan', 'href' => route('employee-benefits.create')],
            ],
            'employees' => $this->employeeOptions(),
            'benefitTypes' => $this->benefitTypeOptions(),
            'currentYear' => (int) now()->year,
        ]);
    }

    public function store(StoreEmployeeBenefitRequest $request): RedirectResponse
    {
        $employeeBenefit = EmployeeBenefit::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Benefit karyawan ditetapkan.']);

        return redirect()->route('employee-benefits.show', $employeeBenefit);
    }

    public function show(EmployeeBenefit $employeeBenefit): Response
    {
        $this->authorize('view', $employeeBenefit);

        $employeeBenefit->loadMissing('employee', 'benefitType', 'claims.decidedBy');

        return Inertia::render('employee-benefits/show', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Benefit Karyawan', 'href' => route('employee-benefits.index')],
                ['title' => 'Detail', 'href' => route('employee-benefits.show', $employeeBenefit)],
            ],
            'employeeBenefit' => [
                'id' => $employeeBenefit->id,
                'employee_name' => $employeeBenefit->employee?->fullName(),
                'employee_no' => $employeeBenefit->employee?->employee_no,
                'benefit_type_name' => $employeeBenefit->benefitType?->name,
                'period_year' => $employeeBenefit->period_year,
                'quota' => (int) $employeeBenefit->quota,
                'used' => $employeeBenefit->usedAmount(),
                'remaining' => $employeeBenefit->remainingQuota(),
                'notes' => $employeeBenefit->notes,
                'claims' => $employeeBenefit->claims->map(fn (BenefitClaim $claim): array => [
                    'id' => $claim->id,
                    'claim_date' => $claim->claim_date?->format('Y-m-d'),
                    'amount' => (int) $claim->amount,
                    'description' => $claim->description,
                    'status' => $claim->status->value,
                    'status_label' => $this->statusLabel($claim->status),
                    'decided_by' => $claim->decidedBy?->name,
                    'decided_at' => $claim->decided_at?->format('Y-m-d H:i'),
                    'decision_note' => $claim->decision_note,
                    'can_decide' => $claim->isPending(),
                ])->all(),
            ],
        ]);
    }

    public function destroy(EmployeeBenefit $employeeBenefit): RedirectResponse
    {
        $this->authorize('delete', $employeeBenefit);

        $employeeBenefit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Benefit karyawan dihapus.']);

        return redirect()->route('employee-benefits.index');
    }

    public function storeClaim(StoreBenefitClaimRequest $request, EmployeeBenefit $employeeBenefit): RedirectResponse
    {
        $this->authorize('update', $employeeBenefit);

        $employeeBenefit->claims()->create([
            ...$request->validated(),
            'status' => RequestStatus::Pending,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klaim benefit ditambahkan.']);

        return back();
    }

    public function decideClaim(DecideBenefitClaimRequest $request, BenefitClaim $benefitClaim): RedirectResponse
    {
        $this->authorize('update', $benefitClaim->employeeBenefit);

        if (! $benefitClaim->isPending()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Klaim sudah diproses.']);

            return back();
        }

        $validated = $request->validated();
        $status = RequestStatus::from($validated['status']);

        if ($status === RequestStatus::Approved
            && (int) $benefitClaim->amount > $benefitClaim->employeeBenefit->remainingQuota()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Klaim melebihi sisa plafon.']);

            return back();
        }

        $benefitClaim->update([
            'status' => $status,
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $validated['decision_note'] ?? null,
        ]);

        $label = $status === RequestStatus::Approved ? 'disetujui' : 'ditolak';
        Inertia::flash('toast', ['type' => 'success', 'message' => "Klaim benefit {$label}."]);

        return back();
    }

    public function destroyClaim(BenefitClaim $benefitClaim): RedirectResponse
    {
        $this->authorize('update', $benefitClaim->employeeBenefit);

        $benefitClaim->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Klaim dihapus.']);

        return back();
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
     * @return Collection<int, array{id: int, label: string, default_quota: int|null}>
     */
    private function benefitTypeOptions(): Collection
    {
        return BenefitType::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'default_quota'])
            ->map(fn (BenefitType $benefitType): array => [
                'id' => $benefitType->id,
                'label' => $benefitType->name,
                'default_quota' => $benefitType->default_quota !== null ? (int) $benefitType->default_quota : null,
            ]);
    }

    private function statusLabel(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::Pending => 'Menunggu',
            RequestStatus::Approved => 'Disetujui',
            RequestStatus::Rejected => 'Ditolak',
            RequestStatus::Revision => 'Revisi',
            RequestStatus::Cancelled => 'Dibatalkan',
        };
    }
}
