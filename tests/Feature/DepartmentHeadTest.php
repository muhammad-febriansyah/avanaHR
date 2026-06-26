<?php

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
use App\Models\ApprovalStepState;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
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
});

/**
 * Replace the overtime flow with a single department_head step.
 */
function departmentHeadOvertimeFlow(): void
{
    ApprovalFlow::where('transaction_type', 'overtime')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'overtime',
        'name' => 'Lembur Kepala Departemen',
        'is_active' => true,
    ]);
    $flow->steps()->create([
        'order' => 1,
        'mode' => 'sequential',
        'approver_type' => 'department_head',
        'min_approvals' => 1,
    ]);
}

it('persists a department head via the update route', function () {
    $department = Department::factory()->create();
    $head = Employee::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('departments.update', $department), [
            'code' => $department->code,
            'name' => $department->name,
            'parent_id' => null,
            'head_employee_id' => $head->id,
        ])
        ->assertRedirect();

    expect($department->fresh()->head_employee_id)->toBe($head->id);
});

it('resolves department_head to the department head user', function () {
    departmentHeadOvertimeFlow();

    // The head employee with a linked login account.
    $headEmployee = Employee::factory()->create();
    $headUser = User::factory()->create([
        'employee_id' => $headEmployee->id,
        'name' => $headEmployee->fullName(),
    ]);

    // The department the requester belongs to, headed by the head employee.
    $department = Department::factory()->create(['head_employee_id' => $headEmployee->id]);

    // The requester employee employed in that department.
    $requester = Employee::factory()->create();
    EmployeeEmployment::factory()->create([
        'employee_id' => $requester->id,
        'department_id' => $department->id,
    ]);

    $overtime = OvertimeRequest::factory()->create([
        'employee_id' => $requester->id,
        'status' => RequestStatus::Pending,
    ]);

    $request = $this->engine->submit($overtime, $this->admin);

    $state = ApprovalStepState::where('request_id', $request->id)
        ->where('step_order', 1)
        ->firstOrFail();

    expect($state->approver_id)->toBe($headUser->id);
});

it('falls back to the manager when the department has no head', function () {
    departmentHeadOvertimeFlow();

    // The manager employee with a linked login account.
    $managerEmployee = Employee::factory()->create();
    $managerUser = User::factory()->create([
        'employee_id' => $managerEmployee->id,
        'name' => $managerEmployee->fullName(),
    ]);

    // Department with no head set.
    $department = Department::factory()->create(['head_employee_id' => null]);

    // The requester reports to the manager employee.
    $requester = Employee::factory()->create();
    EmployeeEmployment::factory()->create([
        'employee_id' => $requester->id,
        'department_id' => $department->id,
        'manager_id' => $managerEmployee->id,
    ]);

    $overtime = OvertimeRequest::factory()->create([
        'employee_id' => $requester->id,
        'status' => RequestStatus::Pending,
    ]);

    $request = $this->engine->submit($overtime, $this->admin);

    $state = ApprovalStepState::where('request_id', $request->id)
        ->where('step_order', 1)
        ->firstOrFail();

    expect($state->approver_id)->toBe($managerUser->id);
});
