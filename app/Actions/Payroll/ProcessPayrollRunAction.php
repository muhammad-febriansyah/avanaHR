<?php

namespace App\Actions\Payroll;

use App\Models\BpjsParameter;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Support\Payroll\BpjsCalculator;
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

        return DB::transaction(function () use ($run, $periodStart, $periodEnd, $daysInMonth, $param): PayrollRun {
            // Idempotent: clear prior payslips (cascade removes lines).
            $run->payslips()->each(fn (Payslip $payslip) => $payslip->delete());

            $grossTotal = 0;
            $netTotal = 0;
            $taxTotal = 0;
            $bpjsTotal = 0;

            foreach ($this->payrollableEmployees($periodStart, $periodEnd) as $employee) {
                $result = $this->buildPayslip($employee, $periodStart, $periodEnd, $daysInMonth, $param);

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

        foreach ($components as $sc) {
            $component = $sc->component;
            $amount = (int) round($sc->amount * ($component->type === 'earning' ? $ratio : 1.0));

            $lines[] = [
                'component_code' => $component->code,
                'component_name' => $component->name,
                'type' => $component->type,
                'amount' => $amount,
            ];

            if ($component->type === 'earning') {
                $earnings += $amount;
                if ($component->is_taxable) {
                    $taxableEarnings += $amount;
                }
            } else {
                $deductionComponents += $amount;
            }
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

        // PPh 21 (TER monthly).
        $taxProfile = $this->effective($employee->taxProfiles(), $periodEnd);
        $taxableEmployerBpjs = $this->taxableEmployerBpjs($bpjs);
        $taxableGross = $taxableEarnings + $taxableEmployerBpjs;

        $tax = 0;
        $ptkp = $taxProfile?->ptkp_status;
        if ($ptkp !== null) {
            $tax = $this->tax->monthlyTax($taxableGross, $ptkp);
        }

        if ($tax > 0) {
            $lines[] = ['component_code' => 'PPH21', 'component_name' => 'PPh 21', 'type' => 'deduction', 'amount' => $tax];
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
                'taxable_gross' => $taxableGross,
                'bpjs' => $bpjs,
                'has_tax_profile' => $taxProfile !== null,
            ],
        ];
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
