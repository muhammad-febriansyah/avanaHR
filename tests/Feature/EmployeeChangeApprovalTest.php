<?php

use App\Actions\Employee\UpdateEmployeeAction;
use App\Approvals\ApprovalEngine;
use App\Enums\ApprovalActionType;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
use App\Models\Employee;
use App\Models\EmployeeChangeRequest;
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
    $this->action = app(UpdateEmployeeAction::class);
    $this->employee = Employee::firstOrFail();
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->manager = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'manager'))->firstOrFail();
});

function employeeChangeFlow(): void
{
    ApprovalFlow::where('transaction_type', 'employee_change')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'employee_change',
        'name' => 'Perubahan Data Karyawan',
        'is_active' => true,
    ]);
    $flow->steps()->create([
        'order' => 1, 'mode' => 'sequential',
        'approver_type' => 'role', 'approver_ref' => 'manager', 'min_approvals' => 1,
    ]);
}

it('applies an employee change immediately when no flow is configured', function () {
    $result = $this->action->handle($this->employee, ['phone' => '081200000001'], $this->admin);

    expect($result['pending'])->toBeFalse()
        ->and($this->employee->fresh()->phone)->toBe('081200000001')
        ->and(EmployeeChangeRequest::where('employee_id', $this->employee->id)->where('status', RequestStatus::Approved)->exists())->toBeTrue();
});

it('holds an employee change for approval when a flow exists', function () {
    employeeChangeFlow();
    $original = $this->employee->phone;

    $result = $this->action->handle($this->employee, ['phone' => '081299999999'], $this->admin);

    expect($result['pending'])->toBeTrue()
        ->and($this->employee->fresh()->phone)->toBe($original) // not applied yet
        ->and(EmployeeChangeRequest::where('employee_id', $this->employee->id)->where('status', RequestStatus::Pending)->exists())->toBeTrue();
});

it('applies the change once approved', function () {
    employeeChangeFlow();
    $this->action->handle($this->employee, ['phone' => '081277777777'], $this->admin);

    $changeRequest = EmployeeChangeRequest::where('employee_id', $this->employee->id)->latest('id')->firstOrFail();
    $this->engine->act($changeRequest->approvalRequest, $this->manager, ApprovalActionType::Approve);

    expect($this->employee->fresh()->phone)->toBe('081277777777')
        ->and($changeRequest->fresh()->status)->toBe(RequestStatus::Approved);
});

it('discards the change when rejected', function () {
    employeeChangeFlow();
    $original = $this->employee->phone;
    $this->action->handle($this->employee, ['phone' => '081266666666'], $this->admin);

    $changeRequest = EmployeeChangeRequest::where('employee_id', $this->employee->id)->latest('id')->firstOrFail();
    $this->engine->act($changeRequest->approvalRequest, $this->manager, ApprovalActionType::Reject, 'Data tidak valid');

    expect($this->employee->fresh()->phone)->toBe($original)
        ->and($changeRequest->fresh()->status)->toBe(RequestStatus::Rejected);
});

it('does not create a change request when only custom fields change', function () {
    $result = $this->action->handle($this->employee, ['custom_fields' => []], $this->admin);

    expect($result['pending'])->toBeFalse()
        ->and(EmployeeChangeRequest::where('employee_id', $this->employee->id)->count())->toBe(0);
});

it('renders a pending employee change in the inbox without lazy-loading violation', function () {
    employeeChangeFlow();
    $this->action->handle($this->employee, ['phone' => '081255555555'], $this->admin);

    $this->actingAs($this->manager)
        ->get(route('approvals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'requests',
            fn ($requests) => collect($requests)->contains(fn ($r) => str_contains((string) ($r['title'] ?? ''), 'Perubahan Data')),
        ));
});
