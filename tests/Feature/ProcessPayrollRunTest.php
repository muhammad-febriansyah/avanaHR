<?php

use App\Actions\Payroll\CommitPaidPayrollRunAction;
use App\Actions\Payroll\ProcessPayrollRunAction;
use App\Models\BpjsParameter;
use App\Models\Employee;
use App\Models\EmployeeBpjsProfile;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeTaxProfile;
use App\Models\OvertimeRequest;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Reimbursement;
use App\Models\Tenant;
use App\Support\CurrentTenant;
use App\Support\Payroll\BpjsCalculator;
use App\Support\Payroll\Pph21AnnualCalculator;
use App\Support\Payroll\Pph21TerCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
});

/** Build one employee with basic+transport+meal, BPJS + tax profiles. */
function seedPayrollEmployee(int $basic = 8_000_000, string $ptkp = 'TK/0', array $employeeOverrides = []): Employee
{
    $tid = test()->tenant->id;
    $employee = Employee::factory()->create(array_merge([
        'tenant_id' => $tid,
        'join_date' => '2020-01-01',
        'resign_date' => null,
        'status' => 'active',
    ], $employeeOverrides));

    $components = [
        ['GAPOK', 'Gaji Pokok', $basic, true, true],
        ['TRANS', 'Transport', 1_000_000, true, false],
        ['MAKAN', 'Makan', 750_000, true, false],
    ];
    foreach ($components as [$code, $name, $amount, $taxable, $bpjsBase]) {
        $component = PayrollComponent::create([
            'tenant_id' => $tid, 'code' => $code.'-'.$employee->id, 'name' => $name,
            'type' => 'earning', 'calc_type' => 'fixed', 'formula' => null,
            'is_taxable' => $taxable, 'is_bpjs_base' => $bpjsBase,
        ]);
        EmployeeSalaryComponent::create([
            'tenant_id' => $tid, 'employee_id' => $employee->id, 'component_id' => $component->id,
            'effective_date' => '2020-01-01', 'amount' => $amount, 'rate' => 0,
        ]);
    }

    EmployeeTaxProfile::create([
        'tenant_id' => $tid, 'employee_id' => $employee->id, 'effective_date' => '2020-01-01',
        'ptkp_status' => $ptkp, 'npwp' => null, 'tax_method' => 'ter', 'beginning_ytd' => 0,
    ]);

    $defaults = config('payroll.bpjs_defaults');
    EmployeeBpjsProfile::create([
        'tenant_id' => $tid, 'employee_id' => $employee->id, 'effective_date' => '2020-01-01',
        'bpjs_kesehatan_no' => null, 'bpjs_tk_no' => null,
        'kesehatan_basis' => $basic, 'tk_basis' => $basic,
        'participation_flags' => ['kesehatan' => true, 'jht' => true, 'jkk' => true, 'jkm' => true, 'jp' => true],
    ]);

    if (! BpjsParameter::query()->exists()) {
        BpjsParameter::create([
            'tenant_id' => $tid, 'effective_date' => '2024-01-01',
            'kes_rate_employee' => $defaults['kes_rate_employee'],
            'kes_rate_employer' => $defaults['kes_rate_employer'],
            'kes_cap' => $defaults['kes_cap'], 'tk_rates' => $defaults['tk_rates'],
        ]);
    }

    return $employee;
}

function makeRun(): PayrollRun
{
    $tid = test()->tenant->id;
    $period = PayrollPeriod::create([
        'tenant_id' => $tid, 'code' => 'PAY-2026-03', 'month' => 3, 'year' => 2026,
        'cutoff_date' => '2026-03-25', 'pay_date' => '2026-03-31', 'status' => 'draft',
    ]);

    return PayrollRun::create([
        'tenant_id' => $tid, 'period_id' => $period->id, 'run_no' => 'RUN-1',
        'type' => 'regular', 'status' => 'draft',
        'gross_total' => 0, 'net_total' => 0, 'tax_total' => 0, 'bpjs_total' => 0,
        'idempotency_key' => 'k1',
    ]);
}

it('computes BPJS split correctly', function () {
    $defaults = config('payroll.bpjs_defaults');
    $param = new BpjsParameter([
        'kes_rate_employee' => $defaults['kes_rate_employee'],
        'kes_rate_employer' => $defaults['kes_rate_employer'],
        'kes_cap' => $defaults['kes_cap'], 'tk_rates' => $defaults['tk_rates'],
    ]);
    $profile = new EmployeeBpjsProfile([
        'kesehatan_basis' => 8_000_000, 'tk_basis' => 8_000_000,
        'participation_flags' => ['kesehatan' => true, 'jht' => true, 'jkk' => true, 'jkm' => true, 'jp' => true],
    ]);

    $bpjs = app(BpjsCalculator::class)->compute($param, $profile);

    expect($bpjs['kesehatan_employee'])->toBe(80_000)   // 1% * 8jt
        ->and($bpjs['jht_employee'])->toBe(160_000)     // 2% * 8jt
        ->and($bpjs['jp_employee'])->toBe(80_000)       // 1% * 8jt
        ->and($bpjs['employee_total'])->toBe(320_000)
        ->and($bpjs['kesehatan_employer'])->toBe(320_000) // 4% * 8jt
        ->and($bpjs['jkk'])->toBe(19_200)               // 0.24% * 8jt
        ->and($bpjs['jkm'])->toBe(24_000);              // 0.30% * 8jt
});

it('caps BPJS Kesehatan basis at the configured cap', function () {
    $defaults = config('payroll.bpjs_defaults');
    $param = new BpjsParameter([
        'kes_rate_employee' => $defaults['kes_rate_employee'],
        'kes_rate_employer' => $defaults['kes_rate_employer'],
        'kes_cap' => $defaults['kes_cap'], 'tk_rates' => $defaults['tk_rates'],
    ]);
    $profile = new EmployeeBpjsProfile([
        'kesehatan_basis' => 20_000_000, 'tk_basis' => 20_000_000,
        'participation_flags' => ['kesehatan' => true, 'jht' => false, 'jkk' => false, 'jkm' => false, 'jp' => false],
    ]);

    $bpjs = app(BpjsCalculator::class)->compute($param, $profile);

    // basis capped at 12jt → 1% = 120k
    expect($bpjs['kesehatan_employee'])->toBe(120_000);
});

it('looks up the TER rate by bracket', function () {
    $ter = app(Pph21TerCalculator::class);
    expect($ter->rate(5_000_000, 'TK/0'))->toBe(0.0)
        ->and($ter->rate(10_113_200, 'TK/0'))->toBe(0.0225)
        ->and($ter->monthlyTax(10_113_200, 'TK/0'))->toBe(227_547);
});

it('throws when the TER category table is not configured', function () {
    app(Pph21TerCalculator::class)->rate(10_000_000, 'K/1'); // category B (empty)
})->throws(RuntimeException::class);

it('generates a payslip with correct gross, BPJS, tax and net', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    $run = makeRun();

    app(ProcessPayrollRunAction::class)->execute($run);

    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect($payslip->gross)->toBe(9_750_000)          // 8jt + 1jt + 750k
        ->and($payslip->bpjs_employee)->toBe(320_000)
        ->and($payslip->bpjs_company)->toBe(819_200)
        ->and($payslip->tax)->toBe(227_547)           // TER 2.25% of 10,113,200 taxable
        ->and($payslip->deductions)->toBe(547_547)    // bpjs_emp + tax
        ->and($payslip->net)->toBe(9_202_453);        // gross - deductions

    expect($payslip->lines()->count())->toBeGreaterThan(3);
});

it('sets run totals and status after processing', function () {
    seedPayrollEmployee(8_000_000, 'TK/0');
    $run = makeRun();

    app(ProcessPayrollRunAction::class)->execute($run);
    $run->refresh();

    expect($run->status)->toBe('calculated')
        ->and($run->gross_total)->toBe(9_750_000)
        ->and($run->net_total)->toBe(9_202_453);
});

it('is idempotent — re-processing does not duplicate payslips', function () {
    seedPayrollEmployee(8_000_000, 'TK/0');
    $run = makeRun();

    $action = app(ProcessPayrollRunAction::class);
    $action->execute($run);
    $action->execute($run);

    expect($run->payslips()->count())->toBe(1);
});

it('prorates earnings for an employee who joined mid-period', function () {
    // Joined 16 March 2026 → 16 of 31 days.
    $employee = seedPayrollEmployee(8_000_000, 'TK/0', ['join_date' => '2026-03-16']);
    $run = makeRun();

    app(ProcessPayrollRunAction::class)->execute($run);
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    // gross = 9,750,000 * 16/31 ≈ 5,032,258 (rounded per component)
    expect($payslip->gross)->toBeLessThan(9_750_000)
        ->and($payslip->gross)->toBeGreaterThan(4_900_000);
});

it('skips tax when the employee has no tax profile', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    $employee->taxProfiles()->delete();
    $run = makeRun();

    app(ProcessPayrollRunAction::class)->execute($run);
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect($payslip->tax)->toBe(0);
});

it('computes a percentage component from the fixed-earning base', function () {
    // Fixed earnings: GAPOK 8jt + TRANS 1jt + MAKAN 750k = 9.75jt.
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    $tid = $this->tenant->id;

    $pct = PayrollComponent::create([
        'tenant_id' => $tid, 'code' => 'TUNJ-'.$employee->id, 'name' => 'Tunjangan Persen',
        'type' => 'earning', 'calc_type' => 'percentage', 'formula' => null,
        'is_taxable' => true, 'is_bpjs_base' => false,
    ]);
    EmployeeSalaryComponent::create([
        'tenant_id' => $tid, 'employee_id' => $employee->id, 'component_id' => $pct->id,
        'effective_date' => '2020-01-01', 'amount' => 0, 'rate' => 10,
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();
    $line = $payslip->lines()->where('component_code', 'TUNJ-'.$employee->id)->firstOrFail();

    expect((int) $line->amount)->toBe(975_000); // 10% of 9,750,000
});

it('adds approved overtime pay as a taxable earning line', function () {
    // Fixed earnings base = 9,750,000 → hourly 9,750,000/173 = 56,358.3815.
    // 2h overtime (workday): 3.5 * hourly = 197,254.
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');

    OvertimeRequest::factory()->approved()->create([
        'tenant_id' => $this->tenant->id,
        'employee_id' => $employee->id,
        'date' => '2026-03-10',
        'planned_minutes' => 120,
        'actual_minutes' => 0,
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();
    $line = $payslip->lines()->where('component_code', 'LEMBUR')->firstOrFail();

    expect((int) $line->amount)->toBe(197_254)
        ->and($line->type)->toBe('earning')
        // Base gross was 9,750,000; overtime lifts it.
        ->and($payslip->gross)->toBe(9_750_000 + 197_254)
        // Overtime is taxable → snapshot taxable_gross includes it.
        ->and((int) $payslip->snapshot['overtime_pay'])->toBe(197_254);
});

it('prefers logged actual minutes over planned for overtime', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');

    OvertimeRequest::factory()->approved()->create([
        'tenant_id' => $this->tenant->id,
        'employee_id' => $employee->id,
        'date' => '2026-03-12',
        'planned_minutes' => 120,
        'actual_minutes' => 60, // actual logged → 1h @ 1.5x = 84,538
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect((int) $payslip->lines()->where('component_code', 'LEMBUR')->value('amount'))->toBe(84_538);
});

it('ignores non-approved and out-of-period overtime', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');

    // Pending — must be ignored.
    OvertimeRequest::factory()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'date' => '2026-03-10', 'planned_minutes' => 120, 'actual_minutes' => 0,
    ]);
    // Approved but outside the March period — must be ignored.
    OvertimeRequest::factory()->approved()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'date' => '2026-04-05', 'planned_minutes' => 120, 'actual_minutes' => 0,
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect($payslip->lines()->where('component_code', 'LEMBUR')->exists())->toBeFalse()
        ->and($payslip->gross)->toBe(9_750_000);
});

it('deducts a loan installment from the payslip', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    EmployeeLoan::factory()->approved()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'principal' => 6_000_000, 'tenor_months' => 6,
        'installment' => 1_000_000, 'outstanding' => 6_000_000,
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect((int) $payslip->lines()->where('component_code', 'LOAN')->value('amount'))->toBe(1_000_000)
        // base deductions 547,547 (bpjs+tax) + 1,000,000 loan
        ->and($payslip->deductions)->toBe(1_547_547)
        ->and($payslip->net)->toBe(8_202_453); // 9,750,000 gross - 1,547,547
});

it('pays a payroll-settled reimbursement as a non-taxable earning', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    $reimbursement = Reimbursement::factory()->approved()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'amount' => 500_000, 'settlement' => 'payroll', 'period_id' => null,
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect((int) $payslip->lines()->where('component_code', 'REIMBURSE')->value('amount'))->toBe(500_000)
        ->and($payslip->gross)->toBe(10_250_000)   // 9,750,000 + 500,000
        ->and($payslip->tax)->toBe(227_547)         // unchanged — reimbursement not taxable
        ->and($payslip->net)->toBe(9_702_453)       // 10,250,000 - 547,547
        ->and($payslip->snapshot['reimbursement_ids'])->toContain($reimbursement->id);
});

it('ignores cash/transfer and unapproved reimbursements in payroll', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    Reimbursement::factory()->approved()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'amount' => 500_000, 'settlement' => 'cash', 'period_id' => null,
    ]);
    Reimbursement::factory()->create([ // pending, payroll
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'amount' => 500_000, 'settlement' => 'payroll', 'period_id' => null,
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect($payslip->lines()->where('component_code', 'REIMBURSE')->exists())->toBeFalse()
        ->and($payslip->gross)->toBe(9_750_000);
});

it('commits loan repayment and reimbursement payout when the run is paid', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    $loan = EmployeeLoan::factory()->approved()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'principal' => 6_000_000, 'tenor_months' => 6,
        'installment' => 1_000_000, 'outstanding' => 6_000_000,
    ]);
    $reimbursement = Reimbursement::factory()->approved()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'amount' => 500_000, 'settlement' => 'payroll', 'period_id' => null,
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $run->update(['status' => 'approved']);

    $commit = app(CommitPaidPayrollRunAction::class);
    $commit->execute($run);

    expect((int) $loan->fresh()->outstanding)->toBe(5_000_000)
        ->and($loan->installments()->where('status', 'paid')->where('period_id', $run->period_id)->count())->toBe(1)
        ->and($reimbursement->fresh()->period_id)->toBe($run->period_id);

    // Idempotent — committing again does not double-deduct or duplicate rows.
    $commit->execute($run);

    expect((int) $loan->fresh()->outstanding)->toBe(5_000_000)
        ->and($loan->installments()->count())->toBe(1);
});

it('stops deducting once the loan is fully repaid', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    // Fully repaid loan (outstanding 0) must not be deducted again.
    EmployeeLoan::factory()->approved()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
        'principal' => 1_000_000, 'tenor_months' => 1,
        'installment' => 1_000_000, 'outstanding' => 0,
    ]);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect($payslip->lines()->where('component_code', 'LOAN')->exists())->toBeFalse();
});

/** A processed run for a given month carrying one employee's YTD payslip. */
function seedYtdPayslip(Employee $employee, int $month, int $taxable, int $withheld): void
{
    $tid = test()->tenant->id;
    $period = PayrollPeriod::create([
        'tenant_id' => $tid, 'code' => "PAY-2026-{$month}", 'month' => $month, 'year' => 2026,
        'cutoff_date' => "2026-{$month}-25", 'pay_date' => "2026-{$month}-28", 'status' => 'draft',
    ]);
    $run = PayrollRun::create([
        'tenant_id' => $tid, 'period_id' => $period->id, 'run_no' => "RUN-{$month}",
        'type' => 'regular', 'status' => 'paid',
        'gross_total' => 0, 'net_total' => 0, 'tax_total' => 0, 'bpjs_total' => 0,
        'idempotency_key' => "k-{$month}",
    ]);
    $run->payslips()->create([
        'tenant_id' => $tid, 'employee_id' => $employee->id,
        'snapshot' => ['taxable_gross' => $taxable], 'gross' => 0, 'deductions' => 0,
        'tax' => $withheld, 'bpjs_employee' => 0, 'bpjs_company' => 0, 'net' => 0, 'is_access_protected' => false,
    ]);
}

function makeDecemberRun(): PayrollRun
{
    $tid = test()->tenant->id;
    $period = PayrollPeriod::create([
        'tenant_id' => $tid, 'code' => 'PAY-2026-12', 'month' => 12, 'year' => 2026,
        'cutoff_date' => '2026-12-25', 'pay_date' => '2026-12-31', 'status' => 'draft',
    ]);

    return PayrollRun::create([
        'tenant_id' => $tid, 'period_id' => $period->id, 'run_no' => 'RUN-12',
        'type' => 'regular', 'status' => 'draft',
        'gross_total' => 0, 'net_total' => 0, 'tax_total' => 0, 'bpjs_total' => 0,
        'idempotency_key' => 'k-12',
    ]);
}

it('applies the annual PPh21 correction in December', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    // YTD (Jan–Nov) collapsed into one prior payslip.
    seedYtdPayslip($employee, 11, 100_000_000, 2_000_000);

    $run = app(ProcessPayrollRunAction::class)->execute(makeDecemberRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    $annualTaxable = 100_000_000 + (int) $payslip->snapshot['taxable_gross'];
    $expectedAnnual = app(Pph21AnnualCalculator::class)->annualTax($annualTaxable, 'TK/0');
    $expectedDecTax = $expectedAnnual - 2_000_000;

    expect((int) $payslip->tax)->toBe($expectedDecTax)
        ->and((int) $payslip->tax)->not->toBe(227_547) // not the plain TER monthly figure
        ->and((int) $payslip->snapshot['annual']['annual_tax'])->toBe($expectedAnnual)
        ->and((int) $payslip->snapshot['annual']['ytd_withheld'])->toBe(2_000_000)
        ->and($payslip->lines()->where('component_code', 'PPH21')->value('component_name'))->toBe('PPh 21 (Koreksi Tahunan)');
});

it('produces a tax refund in December when over-withheld during the year', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    // Over-withheld YTD → December correction is negative (refund) → net > gross-other.
    seedYtdPayslip($employee, 11, 100_000_000, 9_000_000);

    $run = app(ProcessPayrollRunAction::class)->execute(makeDecemberRun());
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect((int) $payslip->tax)->toBeLessThan(0)
        ->and($payslip->net)->toBeGreaterThan($payslip->gross - $payslip->bpjs_employee);
});

it('uses TER (not annual correction) outside December', function () {
    $employee = seedPayrollEmployee(8_000_000, 'TK/0');
    $run = app(ProcessPayrollRunAction::class)->execute(makeRun()); // March
    $payslip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect((int) $payslip->tax)->toBe(227_547)
        ->and($payslip->snapshot['annual'])->toBeNull();
});

it('excludes suspended employees from the run', function () {
    $active = seedPayrollEmployee(8_000_000, 'TK/0');
    $suspended = seedPayrollEmployee(8_000_000, 'TK/0', ['status' => 'suspended']);

    $run = app(ProcessPayrollRunAction::class)->execute(makeRun());

    expect($run->payslips()->where('employee_id', $active->id)->exists())->toBeTrue();
    expect($run->payslips()->where('employee_id', $suspended->id)->exists())->toBeFalse();
});
