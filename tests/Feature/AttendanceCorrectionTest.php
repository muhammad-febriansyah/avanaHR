<?php

use App\Approvals\ApprovalEngine;
use App\Enums\ApprovalActionType;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
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
    $this->engine = app(ApprovalEngine::class);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->employee = Employee::firstOrFail();
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
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

it('opens a pending approval request when submitted through the engine', function () {
    $correction = AttendanceCorrection::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => RequestStatus::Pending,
    ]);

    $request = $this->engine->submit($correction, $this->admin);

    expect($request)->not->toBeNull();
    $this->assertDatabaseHas('approval_requests', [
        'approvable_type' => $correction->getMorphClass(),
        'approvable_id' => $correction->id,
        'status' => 'pending',
    ]);
    expect($correction->fresh()->status)->toBe(RequestStatus::Pending);
});

it('approves a correction via the engine and flags the daily record', function () {
    $correction = AttendanceCorrection::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2026-02-03',
        'status' => RequestStatus::Pending,
    ]);

    $request = $this->engine->submit($correction, $this->admin);
    $this->engine->act($request, $this->manager, ApprovalActionType::Approve);

    expect($correction->fresh()->status)->toBe(RequestStatus::Approved);

    $daily = AttendanceDaily::where('employee_id', $this->employee->id)
        ->whereDate('date', '2026-02-03')
        ->firstOrFail();

    expect($daily->has_correction)->toBeTrue();
});

it('rejects a correction via the engine without flagging the daily record', function () {
    $correction = AttendanceCorrection::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2026-02-04',
        'status' => RequestStatus::Pending,
    ]);

    $request = $this->engine->submit($correction, $this->admin);
    $this->engine->act($request, $this->manager, ApprovalActionType::Reject, 'tidak valid');

    expect($correction->fresh()->status)->toBe(RequestStatus::Rejected);

    expect(AttendanceDaily::where('employee_id', $this->employee->id)
        ->whereDate('date', '2026-02-04')
        ->exists())->toBeFalse();
});

it('auto-approves a correction and flags the daily record when no flow exists', function () {
    ApprovalFlow::where('transaction_type', 'attendance_correction')->delete();

    $correction = AttendanceCorrection::factory()->create([
        'employee_id' => $this->employee->id,
        'date' => '2026-02-05',
        'status' => RequestStatus::Pending,
    ]);

    $request = $this->engine->submit($correction, $this->admin);

    expect($request)->toBeNull();
    expect($correction->fresh()->status)->toBe(RequestStatus::Approved);

    $daily = AttendanceDaily::where('employee_id', $this->employee->id)
        ->whereDate('date', '2026-02-05')
        ->firstOrFail();

    expect($daily->has_correction)->toBeTrue();
});
