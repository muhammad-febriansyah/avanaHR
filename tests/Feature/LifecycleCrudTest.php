<?php

use App\Models\Employee;
use App\Models\EmployeeLifecycleEvent;
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
    $this->employee = Employee::firstOrFail();
});

function lifecyclePayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'type' => 'promotion',
        'effective_date' => '2026-06-01',
        'from_value' => 'Staff',
        'to_value' => 'Supervisor',
        'reason' => 'Kinerja baik',
    ], $overrides);
}

it('renders the lifecycle index', function () {
    $this->actingAs($this->admin)
        ->get(route('lifecycle.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('lifecycle/index')
            ->has('events.data')
            ->has('types'),
        );
});

it('renders the lifecycle create page', function () {
    $this->actingAs($this->admin)
        ->get(route('lifecycle.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('lifecycle/create')
            ->has('types')
            ->has('options.employees'),
        );
});

it('records an event and stores the from/to snapshot', function () {
    $this->actingAs($this->admin)
        ->post(route('lifecycle.store'), lifecyclePayload())
        ->assertRedirect();

    $event = EmployeeLifecycleEvent::where('employee_id', $this->employee->id)
        ->where('type', 'promotion')
        ->latest('id')
        ->firstOrFail();

    expect($event->from_json)->toBe(['value' => 'Staff']);
    expect($event->to_json)->toBe(['value' => 'Supervisor']);
    expect($event->effective_date->format('Y-m-d'))->toBe('2026-06-01');
});

it('validates required fields', function () {
    $this->actingAs($this->admin)
        ->post(route('lifecycle.store'), lifecyclePayload(['type' => '', 'effective_date' => '']))
        ->assertSessionHasErrors(['type', 'effective_date']);
});

it('deletes an event', function () {
    $event = EmployeeLifecycleEvent::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->delete(route('lifecycle.destroy', $event))
        ->assertRedirect();

    $this->assertDatabaseMissing('employee_lifecycle_events', ['id' => $event->id]);
});

it('forbids users without employee.update from recording events', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('employee.update'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('lifecycle.store'), lifecyclePayload())
        ->assertForbidden();
});
