<?php

namespace App\Http\Controllers;

use App\Actions\Payroll\CommitPaidPayrollRunAction;
use App\Actions\Payroll\ProcessPayrollRunAction;
use App\Http\Requests\PayrollRun\StorePayrollRunRequest;
use App\Http\Requests\PayrollRun\UpdatePayrollRunRequest;
use App\Models\AuditLog;
use App\Models\EmployeeMovement;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PayrollRun::class);

        $filters = $request->only('period_id', 'status');

        $runs = PayrollRun::query()
            ->with('period:id,code')
            ->withCount('payslips')
            ->when($filters['period_id'] ?? null, fn ($query, $periodId) => $query->where('period_id', $periodId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PayrollRun $run): array => [
                'id' => $run->id,
                'run_no' => $run->run_no,
                'period_code' => $run->period?->code,
                'type' => $run->type,
                'status' => $run->status,
                'gross_total' => (int) $run->gross_total,
                'net_total' => (int) $run->net_total,
                'payslips_count' => $run->payslips_count,
            ]);

        return Inertia::render('payroll-runs/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Proses Payroll', 'href' => route('payroll-runs.index')],
            ],
            'runs' => $runs,
            'filters' => (object) $filters,
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
            'options' => [
                'periods' => PayrollPeriod::orderByDesc('year')->orderByDesc('month')->get(['id', 'code']),
            ],
        ]);
    }

    public function store(StorePayrollRunRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $period = PayrollPeriod::find($data['period_id']);
        if ($period !== null && in_array($period->status->value, ['locked', 'disbursed'], true)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Periode sudah dikunci, tidak bisa membuat proses payroll baru.']);

            return back();
        }

        PayrollRun::create([
            ...$data,
            'run_no' => $this->nextRunNo($data['period_id']),
            'status' => 'draft',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proses payroll berhasil dibuat.']);

        return back();
    }

    public function update(UpdatePayrollRunRequest $request, PayrollRun $payrollRun): RedirectResponse
    {
        if (! in_array($payrollRun->status, ['draft', 'calculated'], true)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Payroll yang sudah disetujui/dibayar tidak bisa diubah.']);

            return back();
        }

        $payrollRun->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proses payroll berhasil diperbarui.']);

        return back();
    }

    public function destroy(PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('delete', $payrollRun);

        if ($payrollRun->status !== 'draft') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya proses berstatus draft yang dapat dihapus.']);

            return back();
        }

        $payrollRun->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proses payroll berhasil dihapus.']);

        return back();
    }

    public function show(PayrollRun $payrollRun): Response
    {
        $this->authorize('view', $payrollRun);

        $payrollRun->load('period');
        $payslips = $payrollRun->payslips()
            ->with('employee:id,first_name,last_name,employee_no')
            ->orderBy('id')
            ->get()
            ->map(fn (Payslip $payslip): array => [
                'id' => $payslip->id,
                'employee_name' => $payslip->employee?->fullName(),
                'employee_no' => $payslip->employee?->employee_no,
                'gross' => $payslip->gross,
                'deductions' => $payslip->deductions,
                'tax' => $payslip->tax,
                'bpjs_employee' => $payslip->bpjs_employee,
                'net' => $payslip->net,
            ]);

        return Inertia::render('payroll-runs/show', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Proses Payroll', 'href' => route('payroll-runs.index')],
                ['title' => $payrollRun->run_no, 'href' => route('payroll-runs.show', $payrollRun)],
            ],
            'run' => [
                'id' => $payrollRun->id,
                'run_no' => $payrollRun->run_no,
                'status' => $payrollRun->status,
                'period_code' => $payrollRun->period?->code,
                'gross_total' => $payrollRun->gross_total,
                'net_total' => $payrollRun->net_total,
                'tax_total' => $payrollRun->tax_total,
                'bpjs_total' => $payrollRun->bpjs_total,
                'can_process' => $payrollRun->status === 'draft' || $payrollRun->status === 'calculated',
                'can_approve' => $payrollRun->status === 'calculated',
                'can_revert' => $payrollRun->status === 'approved',
                'can_pay' => $payrollRun->status === 'approved',
            ],
            'payslips' => $payslips,
        ]);
    }

    public function process(PayrollRun $payrollRun, ProcessPayrollRunAction $action): RedirectResponse
    {
        $this->authorize('update', $payrollRun);

        if (! in_array($payrollRun->status, ['draft', 'calculated'], true)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Proses payroll yang sudah final tidak bisa dihitung ulang.']);

            return back();
        }

        $action->execute($payrollRun);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payroll berhasil dihitung.']);

        return back();
    }

    /**
     * Approve (lock) a calculated run. Blocked while any employee in the run
     * has a pending exit clearance (BRD: payroll final menunggu clearance).
     */
    public function approve(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('approve', $payrollRun);

        if ($payrollRun->status !== 'calculated') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya payroll berstatus calculated yang bisa disetujui.']);

            return back();
        }

        $blocked = $this->employeesWithPendingClearance($payrollRun);
        if ($blocked->isNotEmpty()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Clearance belum selesai untuk: '.$blocked->implode(', ').'. Selesaikan sebelum approve payroll.',
            ]);

            return back();
        }

        $this->transition($request, $payrollRun, 'approved', 'payroll.approved');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payroll disetujui dan dikunci.']);

        return back();
    }

    /**
     * Revert an approved (but unpaid) run back to calculated for re-processing.
     */
    public function revert(Request $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('approve', $payrollRun);

        if ($payrollRun->status !== 'approved') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya payroll yang sudah disetujui yang bisa dibatalkan persetujuannya.']);

            return back();
        }

        $this->transition($request, $payrollRun, 'calculated', 'payroll.reverted');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Persetujuan dibatalkan, payroll kembali calculated.']);

        return back();
    }

    /**
     * Mark an approved run as paid (after bank disbursement). Final + immutable.
     */
    public function pay(Request $request, PayrollRun $payrollRun, CommitPaidPayrollRunAction $commit): RedirectResponse
    {
        $this->authorize('approve', $payrollRun);

        if ($payrollRun->status !== 'approved') {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya payroll yang sudah disetujui yang bisa ditandai dibayar.']);

            return back();
        }

        // Commit loan repayments + reimbursement payouts before locking as paid.
        $commit->execute($payrollRun);

        $this->transition($request, $payrollRun, 'paid', 'payroll.paid');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Payroll ditandai sudah dibayar.']);

        return back();
    }

    /**
     * Apply a status transition with an audit log entry.
     */
    private function transition(Request $request, PayrollRun $payrollRun, string $to, string $event): void
    {
        $from = $payrollRun->status;
        $payrollRun->update(['status' => $to]);

        AuditLog::create([
            'tenant_id' => $payrollRun->tenant_id,
            'user_id' => $request->user()->id,
            'auditable_type' => PayrollRun::class,
            'auditable_id' => $payrollRun->id,
            'event' => $event,
            'old_values' => ['status' => $from],
            'new_values' => ['status' => $to],
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    /**
     * Names of employees in the run with an exit movement still awaiting clearance.
     *
     * @return Collection<int, string>
     */
    private function employeesWithPendingClearance(PayrollRun $payrollRun): Collection
    {
        $employeeIds = $payrollRun->payslips()->pluck('employee_id');

        return EmployeeMovement::query()
            ->with('employee:id,first_name,last_name')
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('type', ['resign', 'terminate'])
            ->whereIn('status', ['scheduled', 'applied']) // committed exits only, not drafts
            ->where('requires_clearance', true)
            ->get()
            ->filter(fn (EmployeeMovement $movement): bool => $movement->hasPendingClearance())
            ->map(fn (EmployeeMovement $movement): ?string => $movement->employee?->fullName())
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Generate a sequential run number scoped to the period.
     */
    private function nextRunNo(int $periodId): string
    {
        $period = PayrollPeriod::findOrFail($periodId);
        $sequence = PayrollRun::where('period_id', $periodId)->count() + 1;

        return sprintf('RUN-%s-%02d', $period->code, $sequence);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return [
            ['value' => 'regular', 'label' => 'Reguler'],
            ['value' => 'thr', 'label' => 'THR'],
            ['value' => 'bonus', 'label' => 'Bonus'],
            ['value' => 'adjustment', 'label' => 'Penyesuaian'],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'calculated', 'label' => 'Calculated'],
            ['value' => 'approved', 'label' => 'Approved'],
            ['value' => 'paid', 'label' => 'Paid'],
        ];
    }
}
