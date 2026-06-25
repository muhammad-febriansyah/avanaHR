<?php

use App\Models\ClearanceItem;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\EmployeeMovement;
use App\Models\Position;
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
    $this->employee = Employee::query()->whereHas('employments')->firstOrFail();
});

/**
 * Build a movement directly (model is guarded against id only) so the
 * scheduling/command tests do not depend on a factory.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeMovement(array $overrides = []): EmployeeMovement
{
    return EmployeeMovement::create(array_merge([
        'tenant_id' => test()->tenant->id,
        'employee_id' => test()->employee->id,
        'type' => 'transfer',
        'effective_date' => now()->subDay()->toDateString(),
        'status' => 'draft',
    ], $overrides));
}

it('schedules a draft movement', function () {
    $movement = makeMovement([
        'status' => 'draft',
        'effective_date' => now()->addDays(7)->toDateString(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('movements.schedule', $movement))
        ->assertRedirect();

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'scheduled',
    ]);
});

it('unschedules a scheduled movement', function () {
    $movement = makeMovement([
        'status' => 'scheduled',
        'effective_date' => now()->addDays(7)->toDateString(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('movements.unschedule', $movement))
        ->assertRedirect();

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'draft',
    ]);
});

it('rejects scheduling a non-draft movement', function () {
    $movement = makeMovement([
        'status' => 'applied',
    ]);

    $this->actingAs($this->admin)
        ->post(route('movements.schedule', $movement))
        ->assertRedirect();

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'applied',
    ]);
});

it('apply-due command applies a scheduled movement whose effective date has arrived', function () {
    $position = Position::query()->firstOrFail();

    $movement = makeMovement([
        'type' => 'promotion',
        'status' => 'scheduled',
        'effective_date' => now()->subDay()->toDateString(),
        'payload_json' => ['position_id' => $position->id],
    ]);

    $this->artisan('movements:apply-due')->assertSuccessful();

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'applied',
        'applied_by' => null,
    ]);

    $this->assertDatabaseHas('employee_employments', [
        'employee_id' => $this->employee->id,
        'position_id' => $position->id,
        'tenant_id' => $this->tenant->id,
        'end_date' => null,
    ]);
});

it('apply-due command does NOT apply a scheduled movement with a future effective date', function () {
    $movement = makeMovement([
        'type' => 'promotion',
        'status' => 'scheduled',
        'effective_date' => now()->addDays(5)->toDateString(),
    ]);

    $this->artisan('movements:apply-due')->assertSuccessful();

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'scheduled',
    ]);
});

it('apply-due command skips a scheduled exit movement with pending clearance', function () {
    $movement = makeMovement([
        'type' => 'resign',
        'status' => 'scheduled',
        'effective_date' => now()->subDay()->toDateString(),
        'requires_clearance' => true,
    ]);

    $clearance = ClearanceItem::create([
        'tenant_id' => $this->tenant->id,
        'employee_movement_id' => $movement->id,
        'category' => 'hr',
        'label' => 'Exit interview & serah terima HR',
        'status' => 'pending',
    ]);

    $this->artisan('movements:apply-due')->assertSuccessful();

    // Still scheduled (skipped) and employee not resigned.
    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'scheduled',
    ]);

    $this->assertDatabaseMissing('employees', [
        'id' => $this->employee->id,
        'status' => 'resigned',
    ]);

    // Clear the blocker and run again.
    $clearance->update(['status' => 'done']);

    $this->artisan('movements:apply-due')->assertSuccessful();

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'applied',
    ]);

    $this->assertDatabaseHas('employees', [
        'id' => $this->employee->id,
        'status' => 'resigned',
    ]);
});

it('apply-due command applies movements across tenants', function () {
    // Tenant 1 scheduled movement (uses the seeded tenant/employee).
    $tenantOneMovement = makeMovement([
        'type' => 'promotion',
        'status' => 'scheduled',
        'effective_date' => now()->subDay()->toDateString(),
    ]);

    // Provision a second tenant with its own employee + employment.
    $tenantTwo = Tenant::factory()->create();

    app(CurrentTenant::class)->set($tenantTwo);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantTwo->id);

    $employeeTwo = Employee::factory()->create(['tenant_id' => $tenantTwo->id]);
    EmployeeEmployment::factory()->create([
        'tenant_id' => $tenantTwo->id,
        'employee_id' => $employeeTwo->id,
        'effective_date' => now()->subYear()->toDateString(),
    ]);

    $tenantTwoMovement = EmployeeMovement::create([
        'tenant_id' => $tenantTwo->id,
        'employee_id' => $employeeTwo->id,
        'type' => 'promotion',
        'effective_date' => now()->subDay()->toDateString(),
        'status' => 'scheduled',
    ]);

    // Reset to the original tenant context to prove the command sets tenant per movement.
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

    $this->artisan('movements:apply-due')->assertSuccessful();

    // Both movements applied.
    $this->assertDatabaseHas('employee_movements', [
        'id' => $tenantOneMovement->id,
        'status' => 'applied',
    ]);
    $this->assertDatabaseHas('employee_movements', [
        'id' => $tenantTwoMovement->id,
        'status' => 'applied',
    ]);

    // New employment rows carry the correct tenant_id.
    $this->assertDatabaseHas('employee_employments', [
        'employee_id' => $this->employee->id,
        'tenant_id' => $this->tenant->id,
        'end_date' => null,
    ]);
    $this->assertDatabaseHas('employee_employments', [
        'employee_id' => $employeeTwo->id,
        'tenant_id' => $tenantTwo->id,
        'end_date' => null,
    ]);
});
