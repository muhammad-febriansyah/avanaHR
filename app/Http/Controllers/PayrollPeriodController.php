<?php

namespace App\Http\Controllers;

use App\Enums\PayrollPeriodStatus;
use App\Http\Requests\PayrollPeriod\StorePayrollPeriodRequest;
use App\Http\Requests\PayrollPeriod\UpdatePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
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
                'runs_count' => $period->runs_count,
            ]);

        return Inertia::render('payroll-periods/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Periode Payroll', 'href' => route('payroll-periods.index')],
            ],
            'periods' => $periods,
            'statuses' => array_map(
                fn (PayrollPeriodStatus $status): array => ['value' => $status->value, 'label' => ucfirst($status->value)],
                PayrollPeriodStatus::cases(),
            ),
        ]);
    }

    public function store(StorePayrollPeriodRequest $request): RedirectResponse
    {
        PayrollPeriod::create([
            ...$request->validated(),
            'status' => PayrollPeriodStatus::Draft,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode payroll berhasil ditambahkan.']);

        return back();
    }

    public function update(UpdatePayrollPeriodRequest $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $payrollPeriod->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Periode payroll berhasil diperbarui.']);

        return back();
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
}
