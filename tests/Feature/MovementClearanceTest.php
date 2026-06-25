<?php

use App\Models\ClearanceItem;
use App\Models\Employee;
use App\Models\EmployeeMovement;
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
    // Pick a seeded employee that has a linked login user so we can assert
    // access propagation (User.status) when an exit movement is applied.
    $this->employee = Employee::query()->whereHas('user')->firstOrFail();
});

/**
 * Create a resign movement through the store route so the controller also
 * generates the default clearance checklist, then return the fresh model.
 */
function createResignMovement(): EmployeeMovement
{
    test()->actingAs(test()->admin)
        ->post(route('movements.store'), [
            'employee_id' => test()->employee->id,
            'type' => 'resign',
            'effective_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    return EmployeeMovement::query()
        ->where('employee_id', test()->employee->id)
        ->where('type', 'resign')
        ->latest('id')
        ->firstOrFail();
}

it('generates a clearance checklist when an exit movement is created', function () {
    $movement = createResignMovement();

    expect($movement->requires_clearance)->toBeTrue();

    $items = ClearanceItem::query()
        ->where('employee_movement_id', $movement->id)
        ->get();

    expect($items)->toHaveCount(5);
    expect($items->pluck('status')->unique()->all())->toBe(['pending']);

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'requires_clearance' => true,
    ]);
});

it('blocks applying an exit movement while clearance is pending', function () {
    $movement = createResignMovement();

    $employmentCountBefore = $this->employee->employments()->count();

    $this->actingAs($this->admin)
        ->post(route('movements.apply', $movement))
        ->assertRedirect();

    $this->assertDatabaseHas('employee_movements', [
        'id' => $movement->id,
        'status' => 'draft',
    ]);

    expect($this->employee->employments()->count())->toBe($employmentCountBefore);
});

it('marks a clearance item done', function () {
    $movement = createResignMovement();

    $item = ClearanceItem::query()
        ->where('employee_movement_id', $movement->id)
        ->where('status', 'pending')
        ->firstOrFail();

    $this->actingAs($this->admin)
        ->patch(route('movements.clearance.update', $item), ['status' => 'done'])
        ->assertRedirect();

    $this->assertDatabaseHas('clearance_items', [
        'id' => $item->id,
        'status' => 'done',
        'completed_by' => $this->admin->id,
    ]);

    expect($item->fresh()->completed_at)->not->toBeNull();
});

it('applies an exit movement once all clearance items are cleared', function () {
    $movement = createResignMovement();

    $items = ClearanceItem::query()
        ->where('employee_movement_id', $movement->id)
        ->get();

    // Clear the first item through the route (exercises the controller), then
    // bulk-clear the rest directly to keep the test fast.
    $this->actingAs($this->admin)
        ->patch(route('movements.clearance.update', $items->first()), ['status' => 'done'])
        ->assertRedirect();

    ClearanceItem::query()
        ->where('employee_movement_id', $movement->id)
        ->where('status', 'pending')
        ->update([
            'status' => 'done',
            'completed_by' => $this->admin->id,
            'completed_at' => now(),
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
    ]);

    expect($this->employee->fresh()->status->value)->toBe('resigned');
});

it('deactivates the login user when a resign movement is applied', function () {
    $user = User::where('employee_id', $this->employee->id)->firstOrFail();
    expect($user->status)->not->toBe('inactive');

    $movement = createResignMovement();

    ClearanceItem::query()
        ->where('employee_movement_id', $movement->id)
        ->update([
            'status' => 'done',
            'completed_by' => $this->admin->id,
            'completed_at' => now(),
        ]);

    $this->actingAs($this->admin)
        ->post(route('movements.apply', $movement))
        ->assertRedirect();

    expect(User::where('employee_id', $this->employee->id)->firstOrFail()->status)
        ->toBe('inactive');
});

it('forbids updating clearance without employee.update permission', function () {
    $auditor = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $auditor->assignRole('auditor');

    expect($auditor->can('employee.update'))->toBeFalse();

    $movement = createResignMovement();

    $item = ClearanceItem::query()
        ->where('employee_movement_id', $movement->id)
        ->firstOrFail();

    $this->actingAs($auditor)
        ->patch(route('movements.clearance.update', $item), ['status' => 'done'])
        ->assertForbidden();
});
