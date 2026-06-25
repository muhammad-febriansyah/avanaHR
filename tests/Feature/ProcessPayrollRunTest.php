<?php

use App\Actions\Payroll\ProcessPayrollRunAction;
use App\Models\BpjsParameter;
use App\Models\Employee;
use App\Models\EmployeeBpjsProfile;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeTaxProfile;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Tenant;
use App\Support\CurrentTenant;
use App\Support\Payroll\BpjsCalculator;
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
