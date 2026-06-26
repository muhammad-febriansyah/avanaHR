<?php

use App\Approvals\ApprovalEngine;
use App\Enums\ApprovalActionType;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
use App\Models\ApprovalStepState;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\OvertimeRequest;
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

    $this->engine = app(ApprovalEngine::class);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->employee = Employee::firstOrFail();
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
});

function restoreTenant(): void
{
    app(CurrentTenant::class)->set(test()->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId(test()->tenant->id);
}

function makeOt(): OvertimeRequest
{
    return OvertimeRequest::factory()->create([
        'employee_id' => test()->employee->id,
        'status' => RequestStatus::Pending,
    ]);
}

function slaRoleFlow(int $slaHours, ?string $escalateTo = null): void
{
    ApprovalFlow::where('transaction_type', 'overtime')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'overtime',
        'name' => 'Lembur SLA',
        'is_active' => true,
    ]);
    $flow->steps()->create([
        'order' => 1,
        'mode' => 'sequential',
        'approver_type' => 'role',
        'approver_ref' => 'manager',
        'min_approvals' => 1,
        'sla_hours' => $slaHours,
        'escalate_to' => $escalateTo,
    ]);
}

it('arms the SLA deadline on submit', function () {
    slaRoleFlow(24);
    $request = $this->engine->submit(makeOt(), $this->admin);

    $state = ApprovalStepState::where('request_id', $request->id)->first();
    expect($state->due_at)->not->toBeNull();
    expect($state->due_at->gt(now()->addHours(23)))->toBeTrue();
});

it('notifies the step approvers on submit', function () {
    slaRoleFlow(24);
    $request = $this->engine->submit(makeOt(), $this->admin);

    expect(Notification::where('type', 'approval.assigned')->where('user_id', $this->manager->id)->exists())->toBeTrue();
});

it('notifies the requester on final approval', function () {
    $request = $this->engine->submit(makeOt(), $this->admin);
    $this->engine->act($request, $this->manager, ApprovalActionType::Approve);

    expect(Notification::where('type', 'approval.approved')->where('user_id', $this->admin->id)->exists())->toBeTrue();
});

it('sends an SLA reminder once when a step is overdue', function () {
    slaRoleFlow(1); // no escalate_to
    $request = $this->engine->submit(makeOt(), $this->admin);

    // Force the deadline into the past.
    ApprovalStepState::where('request_id', $request->id)->update(['due_at' => now()->subHour()]);

    $this->artisan('approvals:check-sla')->assertSuccessful();
    restoreTenant();

    $state = ApprovalStepState::where('request_id', $request->id)->first();
    expect($state->reminded_at)->not->toBeNull();
    $firstCount = Notification::where('type', 'approval.sla_reminder')->count();
    expect($firstCount)->toBeGreaterThan(0);

    // Running again must not re-send reminders (no escalate_to → no escalation).
    $this->artisan('approvals:check-sla')->assertSuccessful();
    restoreTenant();
    expect(Notification::where('type', 'approval.sla_reminder')->count())->toBe($firstCount);
});

it('escalates after a reminder when escalate_to is set', function () {
    slaRoleFlow(1, 'hr-admin');
    $request = $this->engine->submit(makeOt(), $this->admin);
    ApprovalStepState::where('request_id', $request->id)->update(['due_at' => now()->subHour()]);

    // First run reminds, second run escalates.
    $this->artisan('approvals:check-sla')->assertSuccessful();
    restoreTenant();
    $this->artisan('approvals:check-sla')->assertSuccessful();
    restoreTenant();

    $state = ApprovalStepState::where('request_id', $request->id)->first();
    expect($state->status)->toBe('escalated');
    expect($state->escalated_at)->not->toBeNull();
    expect(Notification::where('type', 'approval.escalated')->where('user_id', $this->admin->id)->exists())->toBeTrue();
});

it('lets the escalation target act once a step is escalated', function () {
    [$m1, $m2] = (function () {
        $managers = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
            ->orderBy('id')->take(2)->get();

        return [$managers[0], $managers[1]];
    })();

    // Step assigned to m1 only; escalates to m2 (a specific user id).
    ApprovalFlow::where('transaction_type', 'overtime')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'overtime',
        'name' => 'Lembur Eskalasi',
        'is_active' => true,
    ]);
    $flow->steps()->create([
        'order' => 1,
        'mode' => 'sequential',
        'approver_type' => 'user',
        'approver_ref' => (string) $m1->id,
        'min_approvals' => 1,
        'sla_hours' => 1,
        'escalate_to' => (string) $m2->id,
    ]);

    $overtime = makeOt();
    $request = $this->engine->submit($overtime, $this->admin);

    // m2 cannot act before escalation.
    expect($this->engine->canAct($request, $m2))->toBeFalse();

    ApprovalStepState::where('request_id', $request->id)->update(['due_at' => now()->subHour()]);
    $this->artisan('approvals:check-sla')->assertSuccessful(); // reminder
    restoreTenant();
    $this->artisan('approvals:check-sla')->assertSuccessful(); // escalate
    restoreTenant();

    // Now m2 (the escalation target) may act and complete the request.
    expect($this->engine->canAct($request->fresh(), $m2))->toBeTrue();
    $this->engine->act($request->fresh(), $m2, ApprovalActionType::Approve);

    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($overtime->fresh()->status)->toBe(RequestStatus::Approved);
});
