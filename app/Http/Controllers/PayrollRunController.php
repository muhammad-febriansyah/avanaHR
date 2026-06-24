<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollRun\StorePayrollRunRequest;
use App\Http\Requests\PayrollRun\UpdatePayrollRunRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
