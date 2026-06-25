<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayslipController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Payslip::class);

        $filters = $request->only('search', 'run_id');

        $payslips = Payslip::query()
            ->with(['employee:id,first_name,last_name,employee_no', 'run:id,run_no,period_id', 'run.period:id,code'])
            ->when($filters['run_id'] ?? null, fn ($query, $runId) => $query->where('run_id', $runId))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->whereHas(
                'employee',
                fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%"),
            ))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Payslip $payslip): array => [
                'id' => $payslip->id,
                'employee_name' => $payslip->employee?->fullName(),
                'employee_no' => $payslip->employee?->employee_no,
                'run_no' => $payslip->run?->run_no,
                'period_code' => $payslip->run?->period?->code,
                'gross' => (int) $payslip->gross,
                'net' => (int) $payslip->net,
            ]);

        return Inertia::render('payslips/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Slip Gaji', 'href' => route('payslips.index')],
            ],
            'payslips' => $payslips,
            'filters' => (object) $filters,
            'options' => [
                'runs' => PayrollRun::orderByDesc('id')->get(['id', 'run_no']),
            ],
        ]);
    }

    public function show(Payslip $payslip): Response
    {
        $this->authorize('view', $payslip);

        $payslip->load([
            'employee:id,first_name,last_name,employee_no',
            'run:id,run_no,period_id',
            'run.period:id,code',
            'lines:id,payslip_id,component_code,component_name,type,amount',
        ]);

        return Inertia::render('payslips/show', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Slip Gaji', 'href' => route('payslips.index')],
                ['title' => 'Detail', 'href' => route('payslips.show', $payslip)],
            ],
            'payslip' => [
                'id' => $payslip->id,
                'employee_name' => $payslip->employee?->fullName(),
                'employee_no' => $payslip->employee?->employee_no,
                'run_no' => $payslip->run?->run_no,
                'period_code' => $payslip->run?->period?->code,
                'gross' => (int) $payslip->gross,
                'deductions' => (int) $payslip->deductions,
                'tax' => (int) $payslip->tax,
                'bpjs_employee' => (int) $payslip->bpjs_employee,
                'bpjs_company' => (int) $payslip->bpjs_company,
                'net' => (int) $payslip->net,
                'lines' => $payslip->lines->map(fn ($line): array => [
                    'id' => $line->id,
                    'component_code' => $line->component_code,
                    'component_name' => $line->component_name,
                    'type' => $line->type,
                    'amount' => (int) $line->amount,
                ])->all(),
            ],
        ]);
    }
}
