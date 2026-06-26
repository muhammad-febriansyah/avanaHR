<?php

use App\Approvals\ApprovalEngine;
use App\Approvals\ApprovalException;
use App\Enums\ApprovalActionType;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\ApprovalAction;
use App\Models\ApprovalDelegation;
use App\Models\ApprovalFlow;
use App\Models\ApprovalStepState;
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

    $this->engine = app(ApprovalEngine::class);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->employee = Employee::firstOrFail();
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
});

function makeOvertime(): OvertimeRequest
{
    return OvertimeRequest::factory()->create([
        'employee_id' => test()->employee->id,
        'status' => RequestStatus::Pending,
    ]);
}

it('auto-approves when no active flow exists', function () {
    ApprovalFlow::where('transaction_type', 'overtime')->delete();

    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    expect($request)->toBeNull();
    expect($overtime->fresh()->status)->toBe(RequestStatus::Approved);
});

it('opens a pending request with step state when a flow exists', function () {
    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    expect($request)->not->toBeNull();
    expect($request->status)->toBe(ApprovalStatus::Pending);
    expect($request->current_step_order)->toBe(1);
    expect($request->stepStates()->count())->toBe(1);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Pending);
    expect($overtime->fresh()->approval_request_id)->toBe($request->id);
});

it('finalizes and runs the approved callback on the last step', function () {
    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    $this->engine->act($request, $this->manager, ApprovalActionType::Approve);

    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Approved);
});

it('rejects and runs the rejected callback', function () {
    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    $this->engine->act($request, $this->manager, ApprovalActionType::Reject, 'tidak perlu');

    expect($request->fresh()->status)->toBe(ApprovalStatus::Rejected);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Rejected);
});

it('blocks the requester from approving their own request (SoD)', function () {
    ApprovalFlow::where('transaction_type', 'overtime')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'overtime',
        'name' => 'HR self',
        'is_active' => true,
    ]);
    $flow->steps()->create([
        'order' => 1,
        'mode' => 'sequential',
        'approver_type' => 'role',
        'approver_ref' => 'hr-admin',
        'min_approvals' => 1,
    ]);

    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    expect(fn () => $this->engine->act($request, $this->admin, ApprovalActionType::Approve))
        ->toThrow(ApprovalException::class);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Pending);
});

it('blocks a non-approver from acting', function () {
    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    // admin has no manager role -> not eligible for the manager step.
    expect($this->engine->canAct($request, $this->admin))->toBeFalse();
    expect(fn () => $this->engine->act($request, $this->admin, ApprovalActionType::Approve))
        ->toThrow(ApprovalException::class);
});

it('advances through sequential multi-step flows', function () {
    ApprovalFlow::where('transaction_type', 'overtime')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'overtime',
        'name' => 'Dua langkah',
        'is_active' => true,
    ]);
    $flow->steps()->createMany([
        ['order' => 1, 'mode' => 'sequential', 'approver_type' => 'role', 'approver_ref' => 'manager', 'min_approvals' => 1],
        ['order' => 2, 'mode' => 'sequential', 'approver_type' => 'role', 'approver_ref' => 'manager', 'min_approvals' => 1],
    ]);

    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    $this->engine->act($request, $this->manager, ApprovalActionType::Approve);
    expect($request->fresh()->current_step_order)->toBe(2);
    expect($request->fresh()->status)->toBe(ApprovalStatus::Pending);

    $this->engine->act($request->fresh(), $this->manager, ApprovalActionType::Approve);
    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Approved);
});

it('matches flow conditions deterministically', function () {
    expect($this->engine->conditionsMatch(null, ['amount' => 5_000_000]))->toBeTrue();
    expect($this->engine->conditionsMatch(['amount_min' => 1_000_000], ['amount' => 5_000_000]))->toBeTrue();
    expect($this->engine->conditionsMatch(['amount_min' => 9_000_000], ['amount' => 5_000_000]))->toBeFalse();
    expect($this->engine->conditionsMatch(['department_id' => [3, 4]], ['department_id' => 3]))->toBeTrue();
    expect($this->engine->conditionsMatch(['department_id' => [3, 4]], ['department_id' => 7]))->toBeFalse();
});

it('shows pending requests in the approver inbox and approves via the route', function () {
    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    $this->actingAs($this->manager)
        ->get(route('approvals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('approvals/index')
            ->has('requests', 1)
            ->where('requests.0.id', $request->id),
        );

    $this->actingAs($this->manager)
        ->post(route('approvals.act', $request), ['action' => 'approve'])
        ->assertRedirect(route('approvals.index'));

    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Approved);
});

it('hides requests from users who are not the approver', function () {
    $this->engine->submit(makeOvertime(), $this->admin);

    $this->actingAs($this->admin)
        ->get(route('approvals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('requests', 0));
});

it('requires a reason when rejecting via the route', function () {
    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    $this->actingAs($this->manager)
        ->post(route('approvals.act', $request), ['action' => 'reject'])
        ->assertSessionHasErrors('reason');

    expect($overtime->fresh()->status)->toBe(RequestStatus::Pending);
});

it('forbids users without approval.act from the inbox', function () {
    $plain = User::query()
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('approval.act'));

    expect($plain)->not->toBeNull();

    $this->actingAs($plain)->get(route('approvals.index'))->assertForbidden();
});

/*
 * Slice 2 — parallel steps + delegation.
 */

function twoManagers(): array
{
    $managers = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->orderBy('id')
        ->take(2)
        ->get();

    expect($managers)->toHaveCount(2);

    return [$managers[0], $managers[1]];
}

function parallelOvertimeFlow(int $minApprovals): void
{
    ApprovalFlow::where('transaction_type', 'overtime')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'overtime',
        'name' => 'Lembur Paralel',
        'is_active' => true,
    ]);
    $flow->steps()->create([
        'order' => 1,
        'mode' => 'parallel',
        'approver_type' => 'role',
        'approver_ref' => 'manager',
        'min_approvals' => $minApprovals,
    ]);
}

it('holds a parallel step open until min_approvals distinct approvers sign off', function () {
    parallelOvertimeFlow(2);
    [$m1, $m2] = twoManagers();

    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    $this->engine->act($request, $m1, ApprovalActionType::Approve);
    expect($request->fresh()->status)->toBe(ApprovalStatus::Pending);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Pending);

    $this->engine->act($request->fresh(), $m2, ApprovalActionType::Approve);
    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Approved);
});

it('blocks the same approver from acting twice on a parallel step', function () {
    parallelOvertimeFlow(2);
    [$m1] = twoManagers();

    $request = $this->engine->submit(makeOvertime(), $this->admin);
    $this->engine->act($request, $m1, ApprovalActionType::Approve);

    expect($this->engine->canAct($request->fresh(), $m1))->toBeFalse();
    expect(fn () => $this->engine->act($request->fresh(), $m1, ApprovalActionType::Approve))
        ->toThrow(ApprovalException::class);
});

it('rejects a parallel step on the first rejection', function () {
    parallelOvertimeFlow(2);
    [$m1] = twoManagers();

    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    $this->engine->act($request, $m1, ApprovalActionType::Reject, 'tidak setuju');

    expect($request->fresh()->status)->toBe(ApprovalStatus::Rejected);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Rejected);
});

function userStepOvertimeFlow(int $approverUserId): void
{
    ApprovalFlow::where('transaction_type', 'overtime')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'overtime',
        'name' => 'Lembur User Spesifik',
        'is_active' => true,
    ]);
    $flow->steps()->create([
        'order' => 1,
        'mode' => 'sequential',
        'approver_type' => 'user',
        'approver_ref' => (string) $approverUserId,
        'min_approvals' => 1,
    ]);
}

it('lets an active delegate act for the assigned approver', function () {
    [$m1, $m2] = twoManagers();
    userStepOvertimeFlow($m1->id);

    ApprovalDelegation::create([
        'from_user_id' => $m1->id,
        'to_user_id' => $m2->id,
        'transaction_types' => null, // all
        'starts_at' => null,
        'ends_at' => null,
    ]);

    $overtime = makeOvertime();
    $request = $this->engine->submit($overtime, $this->admin);

    // m2 is not the assigned approver but holds m1's delegation.
    expect($this->engine->canAct($request, $m2))->toBeTrue();
    $this->engine->act($request, $m2, ApprovalActionType::Approve);

    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Approved);

    // Original approver stays recorded on the step; delegate on the action.
    $state = ApprovalStepState::where('request_id', $request->id)->first();
    expect($state->approver_id)->toBe($m1->id);
    $action = ApprovalAction::where('request_id', $request->id)->first();
    expect($action->actor_id)->toBe($m2->id);
});

it('ignores an expired delegation', function () {
    [$m1, $m2] = twoManagers();
    userStepOvertimeFlow($m1->id);

    ApprovalDelegation::create([
        'from_user_id' => $m1->id,
        'to_user_id' => $m2->id,
        'transaction_types' => null,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDay(),
    ]);

    $request = $this->engine->submit(makeOvertime(), $this->admin);

    expect($this->engine->canAct($request, $m2))->toBeFalse();
    expect(fn () => $this->engine->act($request, $m2, ApprovalActionType::Approve))
        ->toThrow(ApprovalException::class);
});
