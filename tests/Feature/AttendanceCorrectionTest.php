<?php

use App\Enums\RequestStatus;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceDaily;
use App\Models\Employee;
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

it('renders the corrections index', function () {
    AttendanceCorrection::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->get(route('attendance-corrections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendance-corrections/index')
            ->has('corrections.data', 1)
            ->has('statuses'),
        );
});

it('approves a correction and flags the daily record', function () {
    $correction = AttendanceCorrection::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2026-02-03',
    ]);

    $this->actingAs($this->admin)
        ->patch(route('attendance-corrections.decide', $correction), ['status' => 'approved'])
        ->assertRedirect();

    expect($correction->fresh()->status)->toBe(RequestStatus::Approved);

    $daily = AttendanceDaily::where('employee_id', $this->employee->id)
        ->whereDate('date', '2026-02-03')
        ->firstOrFail();

    expect($daily->has_correction)->toBeTrue();
});

it('does not re-decide a processed correction', function () {
    $correction = AttendanceCorrection::factory()->approved()->create([
        'employee_id' => $this->employee->id,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('attendance-corrections.decide', $correction), ['status' => 'rejected'])
        ->assertRedirect();

    expect($correction->fresh()->status)->toBe(RequestStatus::Approved);
});

it('rejects an invalid decision status', function () {
    $correction = AttendanceCorrection::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->patch(route('attendance-corrections.decide', $correction), ['status' => 'pending'])
        ->assertSessionHasErrors('status');
});

it('forbids users without attendance.manage from deciding', function () {
    $correction = AttendanceCorrection::factory()->create(['employee_id' => $this->employee->id]);

    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('attendance.manage'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->patch(route('attendance-corrections.decide', $correction), ['status' => 'approved'])
        ->assertForbidden();
});
