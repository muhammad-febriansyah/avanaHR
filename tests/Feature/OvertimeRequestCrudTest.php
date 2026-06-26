<?php

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\OvertimeRequest;
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

function overtimePayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'date' => '2026-07-01',
        'start_time' => '18:00',
        'end_time' => '20:30',
        'reason' => 'Kejar deadline rilis',
    ], $overrides);
}

it('renders the overtime index', function () {
    $this->actingAs($this->admin)
        ->get(route('overtime-requests.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('overtime-requests/index')
            ->has('requests.data')
            ->has('statuses')
            ->has('options.employees'),
        );
});

it('creates a request and derives planned minutes', function () {
    $this->actingAs($this->admin)
        ->post(route('overtime-requests.store'), overtimePayload())
        ->assertRedirect();

    $overtime = OvertimeRequest::firstOrFail();

    expect($overtime->employee_id)->toBe($this->employee->id);
    expect($overtime->date->format('Y-m-d'))->toBe('2026-07-01');
    expect($overtime->planned_minutes)->toBe(150);
    expect($overtime->status)->toBe(RequestStatus::Pending);
});

it('rejects an end time before the start time', function () {
    $this->actingAs($this->admin)
        ->post(route('overtime-requests.store'), overtimePayload([
            'start_time' => '20:00',
            'end_time' => '18:00',
        ]))
        ->assertSessionHasErrors('end_time');
});

it('blocks editing a non-pending request', function () {
    $overtime = OvertimeRequest::factory()->approved()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->put(route('overtime-requests.update', $overtime), [
            'date' => '2026-07-02',
            'start_time' => '19:00',
            'end_time' => '21:00',
            'reason' => 'ubah',
        ])
        ->assertRedirect();

    expect($overtime->fresh()->date->format('Y-m-d'))->not->toBe('2026-07-02');
});

it('deletes a request', function () {
    $overtime = OvertimeRequest::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->delete(route('overtime-requests.destroy', $overtime))
        ->assertRedirect();

    $this->assertDatabaseMissing('overtime_requests', ['id' => $overtime->id]);
});

it('forbids users without attendance.manage from creating requests', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('attendance.manage'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('overtime-requests.store'), overtimePayload())
        ->assertForbidden();
});
