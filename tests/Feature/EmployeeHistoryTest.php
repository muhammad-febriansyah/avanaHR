<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeEmployment;
use App\Models\JobGrade;
use App\Models\Position;
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
});

it('renders the history page', function () {
    $employee = Employee::firstOrFail();

    $this->actingAs($this->admin)
        ->get(route('employees.history', $employee))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/history')
            ->has('employments')
            ->has('salary'),
        );
});

it('flags the changed field between employment versions', function () {
    $tid = $this->tenant->id;
    $employee = Employee::factory()->create(['tenant_id' => $tid]);
    $company = Company::firstOrFail();
    $department = Department::firstOrFail();
    $grade = JobGrade::firstOrFail();
    [$p1, $p2] = Position::take(2)->get();

    $base = [
        'tenant_id' => $tid, 'employee_id' => $employee->id,
        'company_id' => $company->id, 'department_id' => $department->id,
        'job_grade_id' => $grade->id, 'employment_type' => 'permanent', 'status' => 'active',
    ];
    EmployeeEmployment::create([...$base, 'effective_date' => '2020-01-01', 'position_id' => $p1->id]);
    EmployeeEmployment::create([...$base, 'effective_date' => '2022-06-01', 'position_id' => $p2->id]);

    $this->actingAs($this->admin)
        ->get(route('employees.history', $employee))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('employments.0.effective_date', '2022-06-01')
            ->where('employments.0.changes', ['Jabatan'])
            ->where('employments.1.changes', ['Awal']),
        );
});

it('forbids users without employee.view', function () {
    $employee = Employee::firstOrFail();
    $plain = User::query()
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('employee.view'));

    expect($plain)->not->toBeNull();

    $this->actingAs($plain)->get(route('employees.history', $employee))->assertForbidden();
});
