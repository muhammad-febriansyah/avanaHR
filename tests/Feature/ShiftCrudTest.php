<?php

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\Shift;
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

function shiftPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'SH01',
        'name' => 'Shift Pagi',
        'start_time' => '08:00',
        'end_time' => '17:00',
        'break_min' => 60,
        'is_overnight' => false,
        'late_tolerance_min' => 15,
        'grace_min' => 5,
    ], $overrides);
}

it('renders the shifts index', function () {
    $this->actingAs($this->admin)
        ->get(route('shifts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shifts/index')
            ->has('shifts'),
        );
});

it('creates a shift', function () {
    $this->actingAs($this->admin)
        ->post(route('shifts.store'), shiftPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('shifts', [
        'tenant_id' => $this->tenant->id,
        'code' => 'SH01',
        'name' => 'Shift Pagi',
        'is_overnight' => false,
    ]);
});

it('rejects a duplicate code within the tenant', function () {
    Shift::factory()->create(['code' => 'SH01']);

    $this->actingAs($this->admin)
        ->post(route('shifts.store'), shiftPayload(['code' => 'SH01']))
        ->assertSessionHasErrors('code');
});

it('rejects an invalid time format', function () {
    $this->actingAs($this->admin)
        ->post(route('shifts.store'), shiftPayload(['start_time' => '8 pagi']))
        ->assertSessionHasErrors('start_time');
});

it('updates a shift', function () {
    $shift = Shift::factory()->create(['code' => 'SH02', 'name' => 'Lama']);

    $this->actingAs($this->admin)
        ->put(route('shifts.update', $shift), shiftPayload(['code' => 'SH02', 'name' => 'Baru']))
        ->assertRedirect();

    expect($shift->fresh()->name)->toBe('Baru');
});

it('blocks deleting a shift still used by a schedule', function () {
    $shift = Shift::factory()->create();
    Schedule::create([
        'employee_id' => Employee::firstOrFail()->id,
        'date' => '2026-07-01',
        'shift_id' => $shift->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('shifts.destroy', $shift))
        ->assertRedirect();

    $this->assertNotSoftDeleted($shift);
});

it('deletes an unused shift', function () {
    $shift = Shift::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('shifts.destroy', $shift))
        ->assertRedirect();

    $this->assertSoftDeleted($shift);
});

it('forbids users without attendance.manage from creating shifts', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('attendance.manage'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('shifts.store'), shiftPayload())
        ->assertForbidden();
});
