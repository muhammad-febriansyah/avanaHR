<?php

use App\Actions\Payroll\ProcessPayrollRunAction;
use App\Models\ClearanceItem;
use App\Models\EmployeeMovement;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
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
});

/**
 * Build a draft period + run for the seeded tenant. The seeded salary
 * components are effective 2024-01-01 so a March 2026 period picks them up.
 *
 * Replicated inline from PayrollRunProcessTest (helpers aren't shared across files).
 */
function makeApprovalPeriodRun(): PayrollRun
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

/**
 * Create a draft run and run the payroll engine directly so the run is left
 * in the 'calculated' state with payslips generated (faster than POSTing process).
 */
function makeCalculatedRun(): PayrollRun
{
    $run = makeApprovalPeriodRun();

    app(ProcessPayrollRunAction::class)->execute($run);

    return $run->fresh();
}

it('approves a calculated run', function () {
    $run = makeCalculatedRun();

    expect($run->status)->toBe('calculated');

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.approve', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('approved');

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => PayrollRun::class,
        'auditable_id' => $run->id,
        'event' => 'payroll.approved',
    ]);
});

it('cannot approve a non-calculated run', function () {
    $run = makeApprovalPeriodRun();

    expect($run->status)->toBe('draft');

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.approve', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('draft');
});

it('blocks approval when an employee in the run has pending exit clearance', function () {
    $run = makeCalculatedRun();

    $employeeId = Payslip::where('run_id', $run->id)->firstOrFail()->employee_id;

    $movement = EmployeeMovement::create([
        'tenant_id' => $this->tenant->id,
        'employee_id' => $employeeId,
        'type' => 'resign',
        'effective_date' => now()->toDateString(),
        'status' => 'scheduled',
        'requires_clearance' => true,
        'payload_json' => [],
    ]);

    ClearanceItem::create([
        'tenant_id' => $this->tenant->id,
        'employee_movement_id' => $movement->id,
        'category' => 'hr',
        'label' => 'x',
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.approve', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('calculated');

    $this->assertDatabaseHas('payroll_runs', [
        'id' => $run->id,
        'status' => 'calculated',
    ]);
});

it('allows approval once clearance is cleared', function () {
    $run = makeCalculatedRun();

    $employeeId = Payslip::where('run_id', $run->id)->firstOrFail()->employee_id;

    $movement = EmployeeMovement::create([
        'tenant_id' => $this->tenant->id,
        'employee_id' => $employeeId,
        'type' => 'resign',
        'effective_date' => now()->toDateString(),
        'status' => 'scheduled',
        'requires_clearance' => true,
        'payload_json' => [],
    ]);

    $item = ClearanceItem::create([
        'tenant_id' => $this->tenant->id,
        'employee_movement_id' => $movement->id,
        'category' => 'hr',
        'label' => 'x',
        'status' => 'pending',
    ]);

    // Pending clearance blocks approval.
    $this->actingAs($this->admin)
        ->post(route('payroll-runs.approve', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('calculated');

    // Clear the clearance and retry.
    $item->update(['status' => 'done']);

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.approve', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('approved');
});

it('reverts an approved run to calculated', function () {
    $run = makeCalculatedRun();

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.approve', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('approved');

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.revert', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('calculated');

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => PayrollRun::class,
        'auditable_id' => $run->id,
        'event' => 'payroll.reverted',
    ]);
});

it('marks an approved run as paid', function () {
    $run = makeCalculatedRun();

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.approve', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('approved');

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.pay', $run))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('paid');

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => PayrollRun::class,
        'auditable_id' => $run->id,
        'event' => 'payroll.paid',
    ]);
});

it('cannot re-process an approved run', function () {
    $run = makeCalculatedRun();

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.approve', $run))
        ->assertRedirect();

    $approved = $run->fresh();
    expect($approved->status)->toBe('approved');

    $grossBefore = $approved->gross_total;

    $this->actingAs($this->admin)
        ->post(route('payroll-runs.process', $run))
        ->assertRedirect();

    $after = $run->fresh();

    // Status unchanged and totals not recalculated.
    expect($after->status)->toBe('approved')
        ->and($after->gross_total)->toEqual($grossBefore);
});

it('forbids approval without payroll.approve permission', function () {
    $officer = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $officer->assignRole('payroll-officer');

    expect($officer->can('payroll.approve'))->toBeFalse();

    // Produce a calculated run as admin first.
    $run = makeCalculatedRun();

    $this->actingAs($officer)
        ->post(route('payroll-runs.approve', $run))
        ->assertForbidden();

    expect($run->fresh()->status)->toBe('calculated');
});
