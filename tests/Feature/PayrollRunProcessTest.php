<?php

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollComponent;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
});

/**
 * Build a draft period + run for the seeded tenant. The seeded salary
 * components are effective 2024-01-01 so a March 2026 period picks them up.
 */
function makePeriodRun(): PayrollRun
{
    $tid = test()->tenant->id;

    $period = PayrollPeriod::create([
        'tenant_id' => $tid,
        'code' => 'PAY-2026-03',
        'month' => 3,
        'year' => 2026,
        'cutoff_date' => '2026-03-25',
        'pay_date' => '2026-03-31',
        'status' => 'draft',
    ]);

    return PayrollRun::create([
        'tenant_id' => $tid,
        'period_id' => $period->id,
        'run_no' => 'RUN-T1',
        'type' => 'regular',
        'status' => 'draft',
        'gross_total' => 0,
        'net_total' => 0,
        'tax_total' => 0,
        'bpjs_total' => 0,
        'idempotency_key' => 'tk1',
    ]);
}

it('renders the run show page', function () {
    $run = makePeriodRun();

    $this->actingAs($this->admin)
        ->get(route('payroll-runs.show', $run))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payroll-runs/show')
            ->has('run')
            ->has('payslips'),
        );
});

it('processes a run and generates payslips', function () {
    $run = makePeriodRun();

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.process', $run))
        ->assertRedirect();

    $fresh = $run->fresh();

    expect($fresh->status)->toBe('calculated')
        ->and($fresh->gross_total)->toBeGreaterThan(0);

    expect(Payslip::where('run_id', $run->id)->count())->toBeGreaterThan(0);
});

it('re-processing is idempotent', function () {
    $run = makePeriodRun();

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.process', $run))
        ->assertRedirect();

    $firstCount = Payslip::where('run_id', $run->id)->count();
    expect($firstCount)->toBeGreaterThan(0);

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.process', $run))
        ->assertRedirect();

    expect(Payslip::where('run_id', $run->id)->count())->toBe($firstCount);
});

it('forbids processing without payroll.run permission', function () {
    $auditor = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $auditor->assignRole('auditor');

    expect($auditor->can('payroll.run'))->toBeFalse();

    $run = makePeriodRun();

    $this->actingAs($auditor)
        ->post(route('payroll-runs.process', $run))
        ->assertForbidden();
});

it('cannot process an already-final run', function () {
    $run = makePeriodRun();
    $run->update(['status' => 'approved']);

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.process', $run));

    expect($run->fresh()->status)->toBe('approved');
});

it('renders the employee salary page', function () {
    $employee = Employee::query()->whereHas('salaryComponents')->firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('employees.salary.index', $employee))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/salary')
            ->has('salaryComponents')
            ->has('components'),
        );
});

it('adds a salary component', function () {
    $employee = Employee::query()->whereHas('salaryComponents')->firstOrFail();
    $component = PayrollComponent::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('employees.salary.store', $employee), [
            'component_id' => $component->id,
            'effective_date' => '2026-01-01',
            'amount' => 5_000_000,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('employee_salary_components', [
        'employee_id' => $employee->id,
        'amount' => 5_000_000,
    ]);
});

it('validation fails without required fields', function () {
    $employee = Employee::query()->whereHas('salaryComponents')->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('employees.salary.store', $employee), [])
        ->assertSessionHasErrors(['component_id', 'effective_date', 'amount']);
});

it('deletes a salary component', function () {
    $employee = Employee::query()->whereHas('salaryComponents')->firstOrFail();
    $component = PayrollComponent::query()->firstOrFail();

    $salaryComponent = EmployeeSalaryComponent::create([
        'tenant_id' => $this->tenant->id,
        'employee_id' => $employee->id,
        'component_id' => $component->id,
        'effective_date' => '2026-01-01',
        'amount' => 3_000_000,
        'rate' => 0,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('employees.salary.destroy', $salaryComponent))
        ->assertRedirect();

    $this->assertDatabaseMissing('employee_salary_components', [
        'id' => $salaryComponent->id,
    ]);
});

it('forbids salary edits without payroll.run', function () {
    $auditor = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $auditor->assignRole('auditor');

    expect($auditor->can('payroll.run'))->toBeFalse();

    $employee = Employee::query()->whereHas('salaryComponents')->firstOrFail();
    $component = PayrollComponent::query()->firstOrFail();

    $this->actingAs($auditor)
        ->post(route('employees.salary.store', $employee), [
            'component_id' => $component->id,
            'effective_date' => '2026-01-01',
            'amount' => 5_000_000,
        ])
        ->assertForbidden();
});
