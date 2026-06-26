<?php

namespace App\Actions\Payroll;

use App\Enums\EmployeeStatus;
use App\Enums\RequestStatus;
use App\Models\BpjsParameter;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryComponent;
use App\Models\OvertimeRequest;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Reimbursement;
use App\Support\Payroll\BpjsCalculator;
use App\Support\Payroll\OvertimeCalculator;
use App\Support\Payroll\Pph21AnnualCalculator;
use App\Support\Payroll\Pph21TerCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generates payslips for every payrollable employee in a run's period:
 * earnings/deductions from salary components, BPJS from parameters/profiles,
 * and PPh 21 via the monthly TER method. Idempotent — re-running replaces the
 * run's existing payslips. All money is rupiah (integer).
 */
class ProcessPayrollRunAction
{
    public function __construct(
        private readonly BpjsCalculator $bpjs,
        private readonly Pph21TerCalculator $tax,
        private readonly OvertimeCalculator $overtime,
        private readonly Pph21AnnualCalculator $annualTax,
    ) {}

    public function execute(PayrollRun $run): PayrollRun
    {
        $period = $run->period;
        $periodStart = CarbonImmutable::create($period->year, $period->month, 1);
        $periodEnd = $periodStart->endOfMonth();
        $daysInMonth = $periodStart->daysInMonth;

        $param = BpjsParameter::query()
            ->whereDate('effective_date', '<=', $periodEnd)
            ->orderByDesc('effective_date')
            ->first();

        return DB::transaction(function () use ($run, $period, $periodStart, $periodEnd, $daysInMonth, $param): PayrollRun {
            // Idempotent: clear prior payslips (cascade removes lines).
            $run->payslips()->each(fn (Payslip $payslip) => $payslip->delete());

            $grossTotal = 0;
            $netTotal = 0;
            $taxTotal = 0;
            $bpjsTotal = 0;

            foreach ($this->payrollableEmployees($periodStart, $periodEnd) as $employee) {
                $result = $this->buildPayslip($employee, $period, $periodStart, $periodEnd, $daysInMonth, $param);

                $payslip = $run->payslips()->create([
                    'employee_id' => $employee->id,
                    'snapshot' => $result['snapshot'],
                    'gross' => $result['gross'],
                    'deductions' => $result['deductions'],
                    'tax' => $result['tax'],
                    'bpjs_employee' => $result['bpjs_employee'],
                    'bpjs_company' => $result['bpjs_company'],
                    'net' => $result['net'],
                    'is_access_protected' => false,
                ]);

                foreach ($result['lines'] as $line) {
                    $payslip->lines()->create([...$line, 'tenant_id' => $payslip->tenant_id]);
                }

                $grossTotal += $result['gross'];
                $netTotal += $result['net'];
                $taxTotal += $result['tax'];
                $bpjsTotal += $result['bpjs_employee'] + $result['bpjs_company'];
            }

            $run->update([
                'status' => 'calculated',
                'gross_total' => $grossTotal,
                'net_total' => $netTotal,
                'tax_total' => $taxTotal,
                'bpjs_total' => $bpjsTotal,
            ]);

            return $run->fresh();
        });
    }

    /**
     * Employees with salary configured, employed during the period.
     *
     * @return Collection<int, Employee>
     */
    private function payrollableEmployees(CarbonImmutable $periodStart, CarbonImmutable $periodEnd)
    {
        return Employee::query()
            ->whereDate('join_date', '<=', $periodEnd)
            ->where(fn ($q) => $q->whereNull('resign_date')->orWhereDate('resign_date', '>=', $periodStart))
            // Exclude inactive (suspended) staff. Resigned/terminated are caught
            // by resign_date above and prorated for their final worked days.
            ->where('status', '!=', EmployeeStatus::Suspended->value)
            ->whereHas('salaryComponents', fn ($q) => $q->whereDate('effective_date', '<=', $periodEnd))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{
     *     gross:int, deductions:int, tax:int, bpjs_employee:int,
     *     bpjs_company:int, net:int, lines:list<array<string,mixed>>,
     *     snapshot:array<string,mixed>
     * }
     */
    private function buildPayslip(
        Employee $employee,
        PayrollPeriod $period,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        int $daysInMonth,
        ?BpjsParameter $param,
    ): array {
        $components = $this->effectiveSalaryComponents($employee, $periodEnd);
        $ratio = $this->prorateRatio($employee, $periodStart, $periodEnd, $daysInMonth);

        $lines = [];
        $earnings = 0;
        $taxableEarnings = 0;
        $deductionComponents = 0;
        // Un-prorated monthly fixed earnings — the "upah sebulan" base for overtime.
        $monthlyFixedEarning = 0;

        // Pass 1: fixed components. The prorated fixed-earning total is the base
        // for percentage components.
        $fixedEarningBase = 0;
        $percentageComponents = [];

        foreach ($components as $sc) {
            $component = $sc->component;

            if ($component->calc_type === 'percentage') {
                $percentageComponents[] = $sc;

                continue;
            }

            $amount = (int) round($sc->amount * ($component->type === 'earning' ? $ratio : 1.0));
            $lines[] = $this->componentLine($component, $amount);

            if ($component->type === 'earning') {
                $earnings += $amount;
                $fixedEarningBase += $amount;
                $monthlyFixedEarning += (int) round($sc->amount);
                if ($component->is_taxable) {
                    $taxableEarnings += $amount;
                }
            } else {
                $deductionComponents += $amount;
            }
        }

        // Pass 2: percentage components — rate% of the (already prorated) base.
        foreach ($percentageComponents as $sc) {
            $component = $sc->component;
            $amount = (int) round($fixedEarningBase * (float) ($sc->rate ?? 0) / 100);
            $lines[] = $this->componentLine($component, $amount);

            if ($component->type === 'earning') {
                $earnings += $amount;
                if ($component->is_taxable) {
                    $taxableEarnings += $amount;
                }
            } else {
                $deductionComponents += $amount;
            }
        }

        // Overtime pay (Kepmenaker 102/2004) from approved requests in-period.
        // Taxable earning; not part of the BPJS wage base in this engine.
        $overtimePay = 0;
        if (config('payroll.overtime.enabled', true)) {
            $occurrences = OvertimeRequest::query()
                ->where('employee_id', $employee->id)
                ->where('status', RequestStatus::Approved)
                ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->get()
                ->map(function (OvertimeRequest $ot): array {
                    // Prefer logged actual minutes; fall back to planned when not yet recorded.
                    $actual = (int) ($ot->actual_minutes ?? 0);

                    return [
                        'minutes' => $actual > 0 ? $actual : (int) ($ot->planned_minutes ?? 0),
                        'day_type' => $ot->day_type ?? 'workday',
                    ];
                })
                ->all();

            $overtimePay = $this->overtime->totalPay($monthlyFixedEarning, $occurrences);
        }

        if ($overtimePay > 0) {
            $lines[] = [
                'component_code' => (string) config('payroll.overtime.component_code', 'LEMBUR'),
                'component_name' => (string) config('payroll.overtime.component_name', 'Uang Lembur'),
                'type' => 'earning',
                'amount' => $overtimePay,
            ];
            $earnings += $overtimePay;
            if (config('payroll.overtime.taxable', true)) {
                $taxableEarnings += $overtimePay;
            }
        }

        // Reimbursement payout — approved claims settled via payroll, not yet
        // assigned to a period. Non-taxable earning (expense reimbursement);
        // excluded from the BPJS wage base. Committed (period stamped) on pay.
        $reimbursements = $this->reimbursementPayouts($employee);
        $reimbursementTotal = 0;
        foreach ($reimbursements as $reimbursement) {
            $reimbursementTotal += $reimbursement['amount'];
        }
        if ($reimbursementTotal > 0) {
            $lines[] = [
                'component_code' => 'REIMBURSE',
                'component_name' => 'Reimbursement',
                'type' => 'earning',
                'amount' => $reimbursementTotal,
            ];
            $earnings += $reimbursementTotal;
        }

        // Loan installment deduction — flat monthly installment per active loan,
        // capped at the outstanding balance. Read-only preview; the balance is
        // reduced and the installment recorded when the run is marked paid.
        $loanDeductions = $this->loanDeductions($employee, $period);
        $loanTotal = 0;
        foreach ($loanDeductions as $loan) {
            $loanTotal += $loan['amount'];
        }
        if ($loanTotal > 0) {
            $lines[] = [
                'component_code' => 'LOAN',
                'component_name' => 'Cicilan Pinjaman',
                'type' => 'deduction',
                'amount' => $loanTotal,
            ];
            $deductionComponents += $loanTotal;
        }

        // BPJS.
        $profile = $this->effective($employee->bpjsProfiles(), $periodEnd);
        $bpjs = $param !== null
            ? $this->bpjs->compute($param, $profile)
            : $this->bpjs->compute(new BpjsParameter, null);

        $bpjsEmployee = $bpjs['employee_total'];
        $bpjsCompany = $bpjs['employer_total'];

        foreach ($this->bpjsEmployeeLines($bpjs) as $line) {
            $lines[] = $line;
        }

        // PPh 21 — TER monthly (Jan–Nov); December applies the annual correction.
        $taxProfile = $this->effective($employee->taxProfiles(), $periodEnd);
        $taxableEmployerBpjs = $this->taxableEmployerBpjs($bpjs);
        $taxableGross = $taxableEarnings + $taxableEmployerBpjs;

        $tax = 0;
        $annualSummary = null;
        $ptkp = $taxProfile?->ptkp_status;
        $isDecemberCorrection = config('payroll.annual.enabled', true)
            && (int) $period->month === 12;

        if ($ptkp !== null) {
            if ($isDecemberCorrection) {
                [$ytdTaxable, $ytdWithheld, $ytdPension] = $this->priorYearWithholding($employee, (int) $period->year);
                // beginning_ytd carries prior-employer cumulative taxable income.
                $annualTaxable = $ytdTaxable + (int) ($taxProfile->beginning_ytd ?? 0) + $taxableGross;
                // Employee pension (JHT + JP) is deductible from annual gross.
                $annualPension = $ytdPension + (int) ($bpjs['jht_employee'] ?? 0) + (int) ($bpjs['jp_employee'] ?? 0);
                $annualTax = $this->annualTax->annualTax($annualTaxable, $ptkp, $annualPension);
                $tax = $annualTax - $ytdWithheld; // December withholding (may be a refund)
                $annualSummary = [
                    'annual_taxable' => $annualTaxable,
                    'annual_pension' => $annualPension,
                    'annual_tax' => $annualTax,
                    'ytd_withheld' => $ytdWithheld,
                    'december_correction' => $tax,
                ];
            } else {
                $tax = $this->tax->monthlyTax($taxableGross, $ptkp);
            }
        }

        if ($tax !== 0) {
            $lines[] = [
                'component_code' => 'PPH21',
                'component_name' => $isDecemberCorrection ? 'PPh 21 (Koreksi Tahunan)' : 'PPh 21',
                'type' => 'deduction',
                'amount' => $tax,
            ];
        }

        $gross = $earnings;
        $deductions = $deductionComponents + $bpjsEmployee + $tax;
        $net = $gross - $deductions;

        return [
            'gross' => $gross,
            'deductions' => $deductions,
            'tax' => $tax,
            'bpjs_employee' => $bpjsEmployee,
            'bpjs_company' => $bpjsCompany,
            'net' => $net,
            'lines' => $lines,
            'snapshot' => [
                'employee_no' => $employee->employee_no,
                'employee_name' => $employee->fullName(),
                'ptkp_status' => $ptkp,
                'prorate_ratio' => round($ratio, 4),
                'overtime_pay' => $overtimePay,
                'overtime_base' => $monthlyFixedEarning,
                'reimbursement_total' => $reimbursementTotal,
                'reimbursement_ids' => array_column($reimbursements, 'id'),
                'loan_total' => $loanTotal,
                'loan_deductions' => $loanDeductions,
                'taxable_gross' => $taxableGross,
                'annual' => $annualSummary,
                'bpjs' => $bpjs,
                'has_tax_profile' => $taxProfile !== null,
            ],
        ];
    }

    /**
     * Cumulative taxable income, PPh 21 withheld, and deductible employee
     * pension (JHT + JP) from this year's earlier periods (Jan–Nov), used for
     * the December annual correction.
     *
     * @return array{0:int, 1:int, 2:int} [ytdTaxable, ytdWithheld, ytdPension]
     */
    private function priorYearWithholding(Employee $employee, int $year): array
    {
        $payslips = Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereHas('run.period', fn ($query) => $query->where('year', $year)->where('month', '<', 12))
            ->get(['tax', 'snapshot']);

        $taxable = 0;
        $withheld = 0;
        $pension = 0;
        foreach ($payslips as $payslip) {
            $withheld += (int) $payslip->tax;
            $taxable += (int) ($payslip->snapshot['taxable_gross'] ?? 0);
            $pension += (int) ($payslip->snapshot['bpjs']['jht_employee'] ?? 0)
                + (int) ($payslip->snapshot['bpjs']['jp_employee'] ?? 0);
        }

        return [$taxable, $withheld, $pension];
    }

    /**
     * Approved reimbursements settled via payroll and not yet paid (no period).
     *
     * @return list<array{id:int, amount:int, category:string}>
     */
    private function reimbursementPayouts(Employee $employee): array
    {
        return Reimbursement::query()
            ->where('employee_id', $employee->id)
            ->where('status', RequestStatus::Approved)
            ->where('settlement', 'payroll')
            ->whereNull('period_id')
            ->get(['id', 'amount', 'category'])
            ->map(fn (Reimbursement $reimbursement): array => [
                'id' => $reimbursement->id,
                'amount' => (int) $reimbursement->amount,
                'category' => (string) $reimbursement->category,
            ])
            ->all();
    }

    /**
     * Due loan installments for the period — one flat installment per active
     * loan with an outstanding balance and tenor remaining, capped at the
     * balance, skipping loans already deducted in this period.
     *
     * @return list<array{loan_id:int, amount:int}>
     */
    private function loanDeductions(Employee $employee, PayrollPeriod $period): array
    {
        $loans = EmployeeLoan::query()
            ->where('employee_id', $employee->id)
            ->where('status', RequestStatus::Approved)
            ->where('outstanding', '>', 0)
            ->with('installments:id,loan_id,period_id,status')
            ->get();

        $deductions = [];
        foreach ($loans as $loan) {
            $paidCount = $loan->installments->where('status', 'paid')->count();
            if ($paidCount >= (int) $loan->tenor_months) {
                continue;
            }
            if ($loan->installments->firstWhere('period_id', $period->id) !== null) {
                continue;
            }

            $amount = min((int) $loan->installment, (int) $loan->outstanding);
            if ($amount <= 0) {
                continue;
            }

            $deductions[] = ['loan_id' => (int) $loan->id, 'amount' => $amount];
        }

        return $deductions;
    }

    /**
     * Latest effective salary component per component id.
     *
     * @return Collection<int, EmployeeSalaryComponent>
     */
    private function effectiveSalaryComponents(Employee $employee, CarbonImmutable $periodEnd)
    {
        return $employee->salaryComponents()
            ->with('component')
            ->whereDate('effective_date', '<=', $periodEnd)
            ->orderByDesc('effective_date')
            ->get()
            ->groupBy('component_id')
            ->map(fn ($group) => $group->first())
            ->values();
    }

    /**
     * Latest effective row of an effective-dated hasMany relation.
     */
    private function effective($relation, CarbonImmutable $periodEnd)
    {
        return $relation
            ->whereDate('effective_date', '<=', $periodEnd)
            ->orderByDesc('effective_date')
            ->first();
    }

    /**
     * Prorate ratio for mid-period join/resign (by calendar days).
     */
    private function prorateRatio(Employee $employee, CarbonImmutable $periodStart, CarbonImmutable $periodEnd, int $daysInMonth): float
    {
        $start = $employee->join_date && $employee->join_date->gt($periodStart)
            ? CarbonImmutable::parse($employee->join_date)
            : $periodStart;

        $end = $employee->resign_date && $employee->resign_date->lt($periodEnd)
            ? CarbonImmutable::parse($employee->resign_date)
            : $periodEnd;

        if ($end->lt($start)) {
            return 0.0;
        }

        $workedDays = $start->diffInDays($end) + 1;

        return min(1.0, $workedDays / $daysInMonth);
    }

    /**
     * @return array{component_code:string, component_name:string, type:string, amount:int}
     */
    private function componentLine(PayrollComponent $component, int $amount): array
    {
        return [
            'component_code' => $component->code,
            'component_name' => $component->name,
            'type' => $component->type,
            'amount' => $amount,
        ];
    }

    /**
     * @param  array<string,int>  $bpjs
     * @return list<array<string,mixed>>
     */
    private function bpjsEmployeeLines(array $bpjs): array
    {
        $map = [
            'kesehatan_employee' => 'BPJS Kesehatan (Karyawan)',
            'jht_employee' => 'BPJS JHT (Karyawan)',
            'jp_employee' => 'BPJS JP (Karyawan)',
        ];

        $lines = [];
        foreach ($map as $key => $name) {
            if (($bpjs[$key] ?? 0) > 0) {
                $lines[] = ['component_code' => strtoupper($key), 'component_name' => $name, 'type' => 'deduction', 'amount' => $bpjs[$key]];
            }
        }

        return $lines;
    }

    /**
     * @param  array<string,int>  $bpjs
     */
    private function taxableEmployerBpjs(array $bpjs): int
    {
        $taxable = 0;
        foreach ((array) config('payroll.taxable_employer_components', []) as $key) {
            $taxable += (int) ($bpjs[$key] ?? 0);
        }

        return $taxable;
    }
}
