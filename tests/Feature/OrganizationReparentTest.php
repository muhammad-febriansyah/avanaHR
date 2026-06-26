<?php

use App\Models\Company;
use App\Models\Department;
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
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->company = Company::query()->firstOrFail();
});

it('reparents a department under another department in the same company', function () {
    $parent = Department::factory()->create(['company_id' => $this->company->id]);
    $child = Department::factory()->create([
        'company_id' => $this->company->id,
        'parent_id' => null,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('organization.departments.reparent', $child), [
            'parent_id' => $parent->id,
        ])
        ->assertRedirect();

    expect($child->fresh()->parent_id)->toBe($parent->id);
});

it('moves a department to the company root when parent_id is null', function () {
    $parent = Department::factory()->create(['company_id' => $this->company->id]);
    $child = Department::factory()->create([
        'company_id' => $this->company->id,
        'parent_id' => $parent->id,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('organization.departments.reparent', $child), [
            'parent_id' => null,
        ])
        ->assertRedirect();

    expect($child->fresh()->parent_id)->toBeNull();
});

it('rejects a move that would create a cycle', function () {
    $root = Department::factory()->create([
        'company_id' => $this->company->id,
        'parent_id' => null,
    ]);
    $descendant = Department::factory()->create([
        'company_id' => $this->company->id,
        'parent_id' => $root->id,
    ]);

    // Moving the root under its own descendant must be rejected.
    $this->actingAs($this->admin)
        ->patch(route('organization.departments.reparent', $root), [
            'parent_id' => $descendant->id,
        ])
        ->assertSessionHasErrors('parent_id');

    expect($root->fresh()->parent_id)->toBeNull();
});

it('rejects reparenting under a department in another company', function () {
    $otherCompany = Company::factory()->create();
    $foreignParent = Department::factory()->create(['company_id' => $otherCompany->id]);
    $department = Department::factory()->create([
        'company_id' => $this->company->id,
        'parent_id' => null,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('organization.departments.reparent', $department), [
            'parent_id' => $foreignParent->id,
        ])
        ->assertSessionHasErrors('parent_id');

    expect($department->fresh()->parent_id)->toBeNull();
});

it('forbids users without employee.update from reparenting', function () {
    $auditor = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $auditor->assignRole('auditor');

    expect($auditor->can('employee.update'))->toBeFalse();

    $parent = Department::factory()->create(['company_id' => $this->company->id]);
    $department = Department::factory()->create([
        'company_id' => $this->company->id,
        'parent_id' => null,
    ]);

    $this->actingAs($auditor)
        ->patch(route('organization.departments.reparent', $department), [
            'parent_id' => $parent->id,
        ])
        ->assertForbidden();

    expect($department->fresh()->parent_id)->toBeNull();
});
