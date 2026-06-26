<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Support\Payroll\Pph21AnnualCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Annual PPh 21 reconciliation (Bukti Potong 1721-A1). For the selected year it
 * aggregates each employee's taxable gross and withheld tax from their
 * payslips, recomputes the annual tax via {@see Pph21AnnualCalculator}, and
 * shows the under/over-withheld difference.
 */
class Form1721A1Controller extends Controller
{
    public function __construct(private readonly Pph21AnnualCalculator $calculator) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('report.view'), 403);

        $year = (int) ($request->integer('year') ?: Carbon::now()->year);
        $rows = $this->rows($year);

        return Inertia::render('reports/annual-tax', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Bukti Potong 1721-A1', 'href' => route('reports.annual-tax')],
            ],
            'year' => $year,
            'years' => range(Carbon::now()->year, Carbon::now()->year - 4),
            'rows' => $rows,
            'summary' => $this->summaryOf($rows),
        ]);
    }

    /**
     * Stream the year's 1721-A1 reconciliation as a PDF (dompdf).
     */
    public function print(Request $request): HttpResponse
    {
        abort_unless($request->user()->can('report.view'), 403);

        $year = (int) ($request->integer('year') ?: Carbon::now()->year);
        $rows = $this->rows($year);

        return Pdf::loadView('reports.annual-tax-print', [
            'year' => $year,
            'rows' => $rows,
            'summary' => $this->summaryOf($rows),
            'org' => $request->user()->employee?->currentEmployment?->company?->name,
        ])->stream("1721A1-{$year}.pdf");
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(int $year): Collection
    {
        return Payslip::query()
            ->whereHas('run.period', fn ($query) => $query->where('year', $year))
            ->with([
                'employee:id,first_name,last_name,employee_no',
                'employee.taxProfiles:id,employee_id,npwp,ptkp_status,effective_date',
            ])
            ->get(['id', 'employee_id', 'tax', 'snapshot'])
            ->groupBy('employee_id')
            ->map(fn ($slips) => $this->summarizeEmployee($slips))
            ->sortBy('employee_name')
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    private function summaryOf(Collection $rows): array
    {
        return [
            'employees' => $rows->count(),
            'annual_tax' => (int) $rows->sum('annual_tax'),
            'withheld' => (int) $rows->sum('withheld'),
            'difference' => (int) $rows->sum('difference'),
        ];
    }

    /**
     * @param  Collection<int, Payslip>  $slips
     * @return array<string, mixed>
     */
    private function summarizeEmployee($slips): array
    {
        $employee = $slips->first()->employee;
        $gross = (int) $slips->sum(fn (Payslip $s): int => (int) ($s->snapshot['taxable_gross'] ?? 0));
        $withheld = (int) $slips->sum(fn (Payslip $s): int => (int) $s->tax);
        $pension = (int) $slips->sum(fn (Payslip $s): int => (int) ($s->snapshot['bpjs']['jht_employee'] ?? 0)
            + (int) ($s->snapshot['bpjs']['jp_employee'] ?? 0));

        $latestProfile = $employee?->taxProfiles->sortByDesc('effective_date')->first();
        $ptkp = $latestProfile?->ptkp_status ?? ($slips->last()->snapshot['ptkp_status'] ?? null);

        $annualTax = 0;
        $pkp = 0;
        $occupational = 0;
        $ptkpAmount = 0;
        if ($ptkp !== null) {
            $occupational = $this->calculator->occupationalCost($gross);
            $ptkpAmount = $this->calculator->ptkp($ptkp);
            $pkp = $this->calculator->taxableIncome($gross, $ptkp, $pension);
            $annualTax = $this->calculator->annualTax($gross, $ptkp, $pension);
        }

        return [
            'employee_name' => $employee?->fullName(),
            'employee_no' => $employee?->employee_no,
            'npwp' => $latestProfile?->npwp,
            'ptkp_status' => $ptkp,
            'gross' => $gross,
            'occupational_cost' => $occupational,
            'ptkp' => $ptkpAmount,
            'pkp' => $pkp,
            'annual_tax' => $annualTax,
            'withheld' => $withheld,
            'difference' => $annualTax - $withheld, // positive = kurang bayar, negative = lebih
        ];
    }
}
