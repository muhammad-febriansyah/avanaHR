<?php

use App\Approvals\ApprovalEngine;
use App\Enums\ApprovalActionType;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
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
    $this->leaveType = LeaveType::factory()->create(['allow_negative' => false]);
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
});

function leavePayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'leave_type_id' => test()->leaveType->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-03',
        'reason' => 'Acara keluarga',
    ], $overrides);
}

it('renders the leave requests index', function () {
    $this->actingAs($this->admin)
        ->get(route('leave-requests.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('leave-requests/index')
            ->has('requests.data')
            ->has('statuses')
            ->has('options.leaveTypes')
            ->has('options.employees'),
        );
});

it('creates a pending request routed to approval and computes inclusive days', function () {
    $this->actingAs($this->admin)
        ->post(route('leave-requests.store'), leavePayload())
        ->assertRedirect();

    $leave = LeaveRequest::firstOrFail();

    expect((float) $leave->days)->toBe(3.0);
    expect($leave->status)->toBe(RequestStatus::Pending);

    $this->assertDatabaseHas('approval_requests', [
        'approvable_type' => $leave->getMorphClass(),
        'approvable_id' => $leave->id,
        'status' => 'pending',
    ]);
});

it('rejects an end date before the start date', function () {
    $this->actingAs($this->admin)
        ->post(route('leave-requests.store'), leavePayload([
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-01',
        ]))
        ->assertSessionHasErrors('end_date');
});

it('blocks a request that exceeds the available balance', function () {
    LeaveBalance::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => 2026,
        'entitled' => 2,
        'used' => 0,
        'pending' => 0,
        'expired' => 0,
        'available' => 2,
    ]);

    $this->actingAs($this->admin)
        ->post(route('leave-requests.store'), leavePayload()) // 3 days > 2 available
        ->assertSessionHasErrors('end_date');
});

it('deducts the balance when approved through the engine', function () {
    $balance = LeaveBalance::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => 2026,
        'entitled' => 12,
        'used' => 0,
        'pending' => 0,
        'expired' => 0,
        'available' => 12,
    ]);
    $leave = LeaveRequest::create(leavePayload(['days' => 3, 'status' => RequestStatus::Pending]));

    $engine = app(ApprovalEngine::class);
    $request = $engine->submit($leave, $this->admin);

    expect($request)->not->toBeNull();
    expect($leave->fresh()->status)->toBe(RequestStatus::Pending);

    $engine->act($request, $this->manager, ApprovalActionType::Approve);

    expect($leave->fresh()->status)->toBe(RequestStatus::Approved);

    $balance->refresh();
    expect((float) $balance->used)->toBe(3.0);
    expect((float) $balance->available)->toBe(9.0);
});

it('does not touch the balance when rejected through the engine', function () {
    $balance = LeaveBalance::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => 2026,
        'entitled' => 12,
        'used' => 0,
        'pending' => 0,
        'expired' => 0,
        'available' => 12,
    ]);
    $leave = LeaveRequest::create(leavePayload(['days' => 3, 'status' => RequestStatus::Pending]));

    $engine = app(ApprovalEngine::class);
    $request = $engine->submit($leave, $this->admin);
    $engine->act($request, $this->manager, ApprovalActionType::Reject, 'tidak perlu');

    expect($leave->fresh()->status)->toBe(RequestStatus::Rejected);
    expect((float) $balance->fresh()->available)->toBe(12.0);
});

it('auto-approves and deducts the balance when no flow is configured', function () {
    ApprovalFlow::where('transaction_type', 'leave')->delete();

    $balance = LeaveBalance::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => 2026,
        'entitled' => 12,
        'used' => 0,
        'pending' => 0,
        'expired' => 0,
        'available' => 12,
    ]);

    $this->actingAs($this->admin)
        ->post(route('leave-requests.store'), leavePayload(['end_date' => '2026-07-03']))
        ->assertRedirect();

    $leave = LeaveRequest::firstOrFail();

    expect($leave->status)->toBe(RequestStatus::Approved);

    $balance->refresh();
    expect((float) $balance->used)->toBe(3.0);
    expect((float) $balance->available)->toBe(9.0);
});

it('deletes a request', function () {
    $leave = LeaveRequest::factory()->create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('leave-requests.destroy', $leave))
        ->assertRedirect();

    $this->assertDatabaseMissing('leave_requests', ['id' => $leave->id]);
});

it('forbids users without leave.approve from creating requests', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('leave.approve'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('leave-requests.store'), leavePayload())
        ->assertForbidden();
});
