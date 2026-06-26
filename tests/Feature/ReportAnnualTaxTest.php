<?php

use App\Models\Employee;
use App\Models\EmployeeTaxProfile;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use App\Support\Payroll\Pph21AnnualCalculator;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->employeeUser = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'employee'))->firstOrFail();
    $this->employee = Employee::firstOrFail();

    EmployeeTaxProfile::where('employee_id', $this->employee->id)->delete();
    EmployeeTaxProfile::create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id,
        'effective_date' => '2026-01-01', 'ptkp_status' => 'TK/0', 'npwp' => '09.123.456.7-001.000',
        'tax_method' => 'ter', 'beginning_ytd' => 0,
    ]);

    // One payslip carrying the year's taxable gross + withheld tax.
    $period = PayrollPeriod::create([
        'tenant_id' => $this->tenant->id, 'code' => 'PAY-2026-12', 'month' => 12, 'year' => 2026,
        'cutoff_date' => '2026-12-25', 'pay_date' => '2026-12-31', 'status' => 'draft',
    ]);
    $run = PayrollRun::create([
        'tenant_id' => $this->tenant->id, 'period_id' => $period->id, 'run_no' => 'RUN-12',
        'type' => 'regular', 'status' => 'paid',
        'gross_total' => 0, 'net_total' => 0, 'tax_total' => 0, 'bpjs_total' => 0, 'idempotency_key' => 'rk-12',
    ]);
    $run->payslips()->create([
        'tenant_id' => $this->tenant->id, 'employee_id' => $this->employee->id,
        'snapshot' => ['taxable_gross' => 120_000_000, 'ptkp_status' => 'TK/0'],
        'gross' => 0, 'deductions' => 0, 'tax' => 2_000_000, 'bpjs_employee' => 0, 'bpjs_company' => 0,
        'net' => 0, 'is_access_protected' => false,
    ]);
});

it('renders the 1721-A1 annual reconciliation with computed tax', function () {
    $expectedAnnual = app(Pph21AnnualCalculator::class)->annualTax(120_000_000, 'TK/0'); // 3,000,000

    $this->actingAs($this->admin)
        ->get(route('reports.annual-tax', ['year' => 2026]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reports/annual-tax')
            ->where('year', 2026)
            ->where('rows.0.gross', 120_000_000)
            ->where('rows.0.annual_tax', $expectedAnnual)
            ->where('rows.0.withheld', 2_000_000)
            ->where('rows.0.difference', $expectedAnnual - 2_000_000)
            ->where('rows.0.npwp', '09.123.456.7-001.000')
            ->where('summary.annual_tax', $expectedAnnual)
        );
});

it('streams the 1721-A1 as a PDF', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('reports.annual-tax.print', ['year' => 2026]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('forbids the report (and PDF) for users without report.view', function () {
    $this->actingAs($this->employeeUser)
        ->get(route('reports.annual-tax'))
        ->assertForbidden();
    $this->actingAs($this->employeeUser)
        ->get(route('reports.annual-tax.print'))
        ->assertForbidden();
});
