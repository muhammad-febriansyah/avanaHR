<?php

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\Timesheet;
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

function timesheetPayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'date' => '2026-07-01',
        'hours' => 7.5,
        'note' => 'Sprint backend',
    ], $overrides);
}

it('renders the timesheet index', function () {
    $this->actingAs($this->admin)
        ->get(route('timesheets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('timesheets/index')
            ->has('timesheets.data')
            ->has('options.employees'),
        );
});

it('creates a timesheet', function () {
    $this->actingAs($this->admin)
        ->post(route('timesheets.store'), timesheetPayload())
        ->assertRedirect();

    $timesheet = Timesheet::firstOrFail();

    expect($timesheet->employee_id)->toBe($this->employee->id);
    expect((float) $timesheet->hours)->toBe(7.5);
});

it('rejects hours above 24', function () {
    $this->actingAs($this->admin)
        ->post(route('timesheets.store'), timesheetPayload(['hours' => 30]))
        ->assertSessionHasErrors('hours');
});

it('updates a timesheet', function () {
    $timesheet = Timesheet::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->put(route('timesheets.update', $timesheet), [
            'date' => '2026-07-02',
            'hours' => 4,
            'note' => 'Revisi',
        ])
        ->assertRedirect();

    expect($timesheet->fresh()->note)->toBe('Revisi');
    expect((float) $timesheet->fresh()->hours)->toBe(4.0);
});

it('deletes a timesheet', function () {
    $timesheet = Timesheet::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->delete(route('timesheets.destroy', $timesheet))
        ->assertRedirect();

    $this->assertDatabaseMissing('timesheets', ['id' => $timesheet->id]);
});

it('forbids users without attendance.manage from creating timesheets', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('attendance.manage'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('timesheets.store'), timesheetPayload())
        ->assertForbidden();
});
