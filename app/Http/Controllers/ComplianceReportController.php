<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceReportController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('report.view'), 403);

        $year = (int) ($request->integer('year') ?: Carbon::now()->year);

        $base = PayrollRun::query()
            ->whereHas('period', fn ($query) => $query->where('year', $year));

        $runs = (clone $base)
            ->with('period:id,code,month,year')
            ->withCount('payslips')
            ->withSum('payslips as gross_sum', 'gross')
            ->withSum('payslips as tax_sum', 'tax')
            ->withSum('payslips as bpjs_employee_sum', 'bpjs_employee')
            ->withSum('payslips as bpjs_company_sum', 'bpjs_company')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString()
            ->through(function (PayrollRun $run): array {
                $bpjsEmployee = (int) $run->bpjs_employee_sum;
                $bpjsCompany = (int) $run->bpjs_company_sum;

                return [
                    'id' => $run->id,
                    'run_no' => $run->run_no,
                    'period_code' => $run->period?->code,
                    'period_label' => $this->periodLabel($run->period?->month, $run->period?->year),
                    'status' => $run->status,
                    'employees' => $run->payslips_count,
                    'gross' => (int) $run->gross_sum,
                    'pph21' => (int) $run->tax_sum,
                    'bpjs_employee' => $bpjsEmployee,
                    'bpjs_company' => $bpjsCompany,
                    'bpjs_total' => $bpjsEmployee + $bpjsCompany,
                ];
            });

        return Inertia::render('reports/compliance', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Laporan Kepatuhan', 'href' => route('reports.compliance')],
            ],
            'runs' => $runs,
            'year' => $year,
            'years' => $this->yearOptions(),
            'summary' => $this->summary($base),
        ]);
    }

    /**
     * Year-to-date compliance totals for the selected year.
     *
     * @param  Builder<PayrollRun>  $base
     * @return array<string, int>
     */
    private function summary($base): array
    {
        $totals = (clone $base)
            ->withSum('payslips as tax_sum', 'tax')
            ->withSum('payslips as bpjs_employee_sum', 'bpjs_employee')
            ->withSum('payslips as bpjs_company_sum', 'bpjs_company')
            ->withSum('payslips as gross_sum', 'gross')
            ->get();

        return [
            'runs' => $totals->count(),
            'pph21' => (int) $totals->sum('tax_sum'),
            'bpjs' => (int) ($totals->sum('bpjs_employee_sum') + $totals->sum('bpjs_company_sum')),
            'gross' => (int) $totals->sum('gross_sum'),
        ];
    }

    private function periodLabel(?int $month, ?int $year): ?string
    {
        if (! $month || ! $year) {
            return null;
        }

        return Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM YYYY');
    }

    /**
     * @return list<int>
     */
    private function yearOptions(): array
    {
        $current = Carbon::now()->year;

        return range($current, $current - 4);
    }
}
