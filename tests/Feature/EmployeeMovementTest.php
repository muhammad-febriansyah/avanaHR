<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeMovement;
use App\Models\Position;
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
    $this->employee = Employee::query()->whereHas('employments')->firstOrFail();
});

/**
 * Build a draft movement directly (model is guarded against id only) so the
 * apply/destroy tests do not depend on a factory.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeDraftMovement(array $overrides = []): EmployeeMovement
{
    return EmployeeMovement::create(array_merge([
        'tenant_id' => test()->tenant->id,
        'employee_id' => test()->employee->id,
        'type' => 'transfer',
        'effective_date' => now()->subDay()->toDateString(),
        'status' => 'draft',
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function movementPayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'type' => 'promotion',
        'effective_date' => now()->toDateString(),
    ], $overrides);
}

it('renders the movement index', function () {
    $this->actingAs($this->admin)
        ->get(route('movements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('movements/index')
            ->has('movements')
            ->has('types')
            ->has('statuses'),
        );
});

it('renders the create page', function () {
    $this->actingAs($this->admin)
        ->get(route('movements.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('movements/create')
            ->has('employees')
            ->has('options'),
        );
});

it('creates a draft movement', function () {
    $position = Position::query()->firstOrFail();

    $this->actingAs($this->admin)
        ->post(route('movements.store'), movementPayload([
            'position_id' => $position->id,
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('employee_movements', [
        'employee_id' => $this->employee->id,
        'status' => 'draft',
        'tenant_id' => $this->tenant->id,
    ]);
});

it('fails validation without required fields', function () {
    $this->actingAs($this->admin)
        ->post(route('movements.store'), [])
        ->assertSessionHasErrors(['employee_id', 'type', 'effective_date']);
});

it('marks an exit movement as requiring clearance', function () {
    $this->actingAs($this->admin)
        ->post(route('movements.store'), movementPayload([
            'type' => 'resign',
        ]))
        ->assertRedirect();

    $this->assertDatabaseHas('employee_movements', [
        'employee_id' => $this->employee->id,
        'type' => 'resign',
        'requires_clearance' => true,
    ]);
});

it('applies a draft movement creating new employment and lifecycle event', function () {
    $current = $this->employee->currentEmployment()->firstOrFail();

    $targetDepartment = Department::query()
        ->where('id', '!=', $current->department_id)
        ->firstOrFail();

    $movement = makeDraftMovement([
        'type' => 'transfer',
        'effective_date' => now()->toDateString(),
        'payload_json' => ['department_id' => $targetDepartment->id],
    ]);

    $this->actingAs($this->admin)
        ->post(route('movements.apply', $movement))
        ->assertRedirect();

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'applied',
    ]);

    $this->assertDatabaseHas('employee_employments', [
        'employee_id' => $this->employee->id,
        'department_id' => $targetDepartment->id,
    ]);

    $this->assertDatabaseHas('employee_lifecycle_events', [
        'employee_id' => $this->employee->id,
        'type' => 'transfer',
    ]);
});

it('marks employee resigned when a resign movement is applied', function () {
    $movement = makeDraftMovement([
        'type' => 'resign',
        'effective_date' => now()->toDateString(),
        'requires_clearance' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('movements.apply', $movement))
        ->assertRedirect();

    $this->assertDatabaseHas('employees', [
        'id' => $this->employee->id,
        'status' => 'resigned',
    ]);
});

it('deletes a draft movement', function () {
    $movement = makeDraftMovement();

    $this->actingAs($this->admin)
        ->delete(route('movements.destroy', $movement))
        ->assertRedirect();

    $this->assertDatabaseMissing('employee_movements', [
        'id' => $movement->id,
    ]);
});

it('forbids users without employee.update from creating a movement', function () {
    $auditor = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $auditor->assignRole('auditor');

    expect($auditor->can('employee.update'))->toBeFalse();

    $this->actingAs($auditor)
        ->post(route('movements.store'), movementPayload())
        ->assertForbidden();

    $this->actingAs($auditor)
        ->get(route('movements.create'))
        ->assertForbidden();
});

it('applies a movement for an employee without a current employment', function () {
    $orphan = Employee::factory()->create(['tenant_id' => $this->tenant->id]);

    $movement = EmployeeMovement::create([
        'tenant_id' => $this->tenant->id,
        'employee_id' => $orphan->id,
        'type' => 'promotion',
        'effective_date' => now()->toDateString(),
        'status' => 'draft',
    ]);

    $this->actingAs($this->admin)
        ->post(route('movements.apply', $movement))
        ->assertRedirect();

    $this->assertDatabaseHas('employee_movements', ['id' => $movement->id, 'status' => 'applied']);
    $this->assertDatabaseHas('employee_employments', [
        'employee_id' => $orphan->id,
        'employment_type' => 'permanent',
    ]);
});
