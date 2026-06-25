<?php

use App\Models\AttendanceDaily;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

it('renders the attendance recap index', function () {
    AttendanceDaily::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => Carbon::today()->format('Y-m-d'),
    ]);

    $this->actingAs($this->admin)
        ->get(route('attendance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('attendance/index')
            ->has('rows.data', 1)
            ->has('statuses'),
        );
});

it('derives clock in and out from the raw logs', function () {
    $date = Carbon::today();

    AttendanceDaily::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => $date->format('Y-m-d'),
    ]);
    AttendanceLog::factory()->create([
        'employee_id' => $this->employee->id,
        'type' => 'in',
        'logged_at' => $date->copy()->setTime(8, 5),
    ]);
    AttendanceLog::factory()->create([
        'employee_id' => $this->employee->id,
        'type' => 'out',
        'logged_at' => $date->copy()->setTime(17, 30),
    ]);

    $this->actingAs($this->admin)
        ->get(route('attendance.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.data.0.clock_in', '08:05')
            ->where('rows.data.0.clock_out', '17:30'),
        );
});

it('filters the recap by date range', function () {
    AttendanceDaily::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2026-01-10',
    ]);

    $this->actingAs($this->admin)
        ->get(route('attendance.index', ['from' => '2026-01-01', 'to' => '2026-01-31']))
        ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1));
});

it('forbids users without attendance.view from the recap', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('attendance.view'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('attendance.index'))
        ->assertForbidden();
});
