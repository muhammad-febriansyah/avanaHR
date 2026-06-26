<?php

namespace App\Http\Controllers;

use App\Enums\PayrollPeriodStatus;
use App\Http\Requests\PayrollPeriod\StorePayrollPeriodRequest;
use App\Http\Requests\PayrollPeriod\UpdatePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollPeriodController extends Controller
{
    use AuthorizesRequests;

    public function index(): Response
    {
        $this->authorize('viewAny', PayrollPeriod::class);

        $periods = PayrollPeriod::query()
            ->withCount('runs')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get(['id', 'code', 'month', 'year', 'cutoff_date', 'pay_date', 'status'])
            ->map(fn (PayrollPeriod $period): array => [
                'id' => $period->id,
                'code' => $period->code,
                'month' => (int) $period->month,
                'year' => (int) $period->year,
                'cutoff_date' => $period->cutoff_date?->format('Y-m-d'),
                'pay_date' => $period->pay_date?->format('Y-m-d'),
                'status' => $period->status->value,
                'can_close' => $period->status === PayrollPeriodStatus::Draft,
                'can_reopen' => $period->status === PayrollPeriodStatus::Locked,
                'runs_count' => $period->runs_count,
            ]);

        return Inertia::render('payroll-periods/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Periode Payroll', 'href' => route('payroll-periods.index')],
            ],
            'periods' => $periods,
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PayrollPeriod::class);

        return Inertia::render('payroll-periods/create', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Periode Payroll', 'href' => route('payroll-periods.index')],
                ['title' => 'Tambah', 'href' => route('payroll-periods.create')],
            ],
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(StorePayrollPeriodRequest $request): RedirectResponse
    {
        PayrollPeriod::create([
            ...$request->validated(),
            'status' => PayrollPeriodStatus::Draft,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode payroll berhasil ditambahkan.']);

        return redirect()->route('payroll-periods.index');
    }

    public function edit(PayrollPeriod $payrollPeriod): Response
    {
        $this->authorize('update', $payrollPeriod);

        return Inertia::render('payroll-periods/edit', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Periode Payroll', 'href' => route('payroll-periods.index')],
                ['title' => 'Edit', 'href' => route('payroll-periods.edit', $payrollPeriod)],
            ],
            'period' => [
                'id' => $payrollPeriod->id,
                'code' => $payrollPeriod->code,
                'month' => (int) $payrollPeriod->month,
                'year' => (int) $payrollPeriod->year,
                'cutoff_date' => $payrollPeriod->cutoff_date?->format('Y-m-d'),
                'pay_date' => $payrollPeriod->pay_date?->format('Y-m-d'),
                'status' => $payrollPeriod->status->value,
            ],
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(UpdatePayrollPeriodRequest $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $payrollPeriod->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode payroll berhasil diperbarui.']);

        return redirect()->route('payroll-periods.index');
    }

    public function destroy(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $this->authorize('delete', $payrollPeriod);

        if ($payrollPeriod->status !== PayrollPeriodStatus::Draft) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya periode berstatus draft yang dapat dihapus.']);

            return back();
        }

        if ($payrollPeriod->runs()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Periode masih memiliki proses payroll.']);

            return back();
        }

        $payrollPeriod->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode payroll berhasil dihapus.']);

        return back();
    }

    public function close(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.approve'), 403);

        if ($payrollPeriod->status !== PayrollPeriodStatus::Draft) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya periode draft yang bisa dikunci.']);

            return back();
        }

        $payrollPeriod->update(['status' => PayrollPeriodStatus::Locked]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode dikunci.']);

        return back();
    }

    public function reopen(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        abort_unless($request->user()->can('payroll.approve'), 403);

        if ($payrollPeriod->status !== PayrollPeriodStatus::Locked) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya periode terkunci yang bisa dibuka.']);

            return back();
        }

        $payrollPeriod->update(['status' => PayrollPeriodStatus::Draft]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode dibuka kembali.']);

        return back();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (PayrollPeriodStatus $status): array => ['value' => $status->value, 'label' => ucfirst($status->value)],
            PayrollPeriodStatus::cases(),
        );
    }
}
