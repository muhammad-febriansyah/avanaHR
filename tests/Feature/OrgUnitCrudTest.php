<?php

use App\Models\Company;
use App\Models\Department;
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
    $this->company = Company::firstOrFail();
});

it('renders the org units index', function () {
    $this->actingAs($this->admin)
        ->get(route('departments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('org-units/index')
            ->has('departments')
            ->has('positions')
            ->has('options.departments')
            ->has('options.jobLevels')
            ->has('options.jobGrades'),
        );
});

it('creates a department with the resolved tenant and company', function () {
    $this->actingAs($this->admin)
        ->post(route('departments.store'), ['code' => 'DEP900', 'name' => 'IT Operations'])
        ->assertRedirect();

    $this->assertDatabaseHas('departments', [
        'tenant_id' => $this->tenant->id,
        'company_id' => $this->company->id,
        'code' => 'DEP900',
        'name' => 'IT Operations',
    ]);
});

it('rejects a duplicate department code within the tenant', function () {
    Department::factory()->create(['company_id' => $this->company->id, 'code' => 'DEP900']);

    $this->actingAs($this->admin)
        ->post(route('departments.store'), ['code' => 'DEP900', 'name' => 'Dup'])
        ->assertSessionHasErrors('code');
});

it('prevents a department from being its own parent', function () {
    $department = Department::factory()->create(['company_id' => $this->company->id]);

    $this->actingAs($this->admin)
        ->put(route('departments.update', $department), [
            'code' => $department->code,
            'name' => $department->name,
            'parent_id' => $department->id,
        ])
        ->assertSessionHasErrors('parent_id');
});

it('blocks deleting a department that still has positions', function () {
    $department = Department::factory()->create(['company_id' => $this->company->id]);
    Position::factory()->create(['department_id' => $department->id]);

    $this->actingAs($this->admin)
        ->delete(route('departments.destroy', $department))
        ->assertRedirect();

    expect($department->fresh())->not->toBeNull();
});

it('deletes an empty department', function () {
    $department = Department::factory()->create(['company_id' => $this->company->id]);

    $this->actingAs($this->admin)
        ->delete(route('departments.destroy', $department))
        ->assertRedirect();

    $this->assertSoftDeleted($department);
});

it('creates a position with optional level and grade omitted', function () {
    $department = Department::factory()->create(['company_id' => $this->company->id]);

    $this->actingAs($this->admin)
        ->post(route('positions.store'), [
            'code' => 'POS900',
            'name' => 'DevOps Engineer',
            'department_id' => $department->id,
            'job_level_id' => null,
            'job_grade_id' => null,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('positions', [
        'tenant_id' => $this->tenant->id,
        'code' => 'POS900',
        'department_id' => $department->id,
        'job_level_id' => null,
        'job_grade_id' => null,
    ]);
});

it('rejects a position without a department', function () {
    $this->actingAs($this->admin)
        ->post(route('positions.store'), ['code' => 'POS901', 'name' => 'No Dept'])
        ->assertSessionHasErrors('department_id');
});

it('forbids users without employee.create from creating org units', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->firstOrFail();

    $this->actingAs($employee)
        ->post(route('departments.store'), ['code' => 'DEP901', 'name' => 'Nope'])
        ->assertForbidden();
});
