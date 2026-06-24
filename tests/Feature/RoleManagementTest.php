<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
});

function makeRole(string $name, int $teamId): Role
{
    return Role::create(['name' => $name, 'guard_name' => 'web', 'team_id' => $teamId]);
}

it('renders the roles index with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/index')
            ->has('roles')
            ->has('breadcrumbs', 2),
        );
});

it('renders the create page with permission groups', function () {
    $this->actingAs($this->admin)
        ->get(route('roles.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/create')
            ->has('permissionGroups')
            ->has('breadcrumbs', 3),
        );
});

it('creates a role with selected permissions', function () {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), [
            'name' => 'supervisor-cabang',
            'permissions' => ['employee.view', 'leave.approve'],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::where('name', 'supervisor-cabang')->where('team_id', $this->tenant->id)->first();

    expect($role)->not->toBeNull();
    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['employee.view', 'leave.approve']);
});

it('requires a name', function () {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), ['name' => '', 'permissions' => []])
        ->assertSessionHasErrors('name');
});

it('rejects a duplicate role name within the tenant', function () {
    $this->actingAs($this->admin)
        ->post(route('roles.store'), ['name' => 'hr-admin'])
        ->assertSessionHasErrors('name');
});

it('updates permissions of a role', function () {
    $role = makeRole('supervisor-cabang', $this->tenant->id);
    $role->syncPermissions(['employee.view']);

    $this->actingAs($this->admin)
        ->put(route('roles.update', $role), [
            'name' => 'supervisor-cabang',
            'permissions' => ['employee.view', 'employee.update', 'leave.approve'],
        ])
        ->assertRedirect(route('roles.index'));

    expect($role->fresh()->permissions)->toHaveCount(3);
});

it('keeps the name of a system role immutable on update', function () {
    $role = Role::where('name', 'hr-admin')->where('team_id', $this->tenant->id)->firstOrFail();

    $this->actingAs($this->admin)
        ->put(route('roles.update', $role), [
            'name' => 'hr-admin-renamed',
            'permissions' => ['employee.view'],
        ])
        ->assertRedirect(route('roles.index'));

    expect($role->fresh()->name)->toBe('hr-admin');
});

it('deletes a custom role', function () {
    $role = makeRole('temporary', $this->tenant->id);

    $this->actingAs($this->admin)
        ->delete(route('roles.destroy', $role))
        ->assertRedirect();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

it('blocks deleting a system role', function () {
    $role = Role::where('name', 'hr-admin')->where('team_id', $this->tenant->id)->firstOrFail();

    $this->actingAs($this->admin)
        ->delete(route('roles.destroy', $role))
        ->assertRedirect();

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

it('blocks deleting a role still assigned to users', function () {
    $role = makeRole('temporary', $this->tenant->id);
    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->assignRole($role);

    $this->actingAs($this->admin)
        ->delete(route('roles.destroy', $role))
        ->assertRedirect();

    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});

it('prevents touching a role from another tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $foreignRole = makeRole('foreign', $otherTenant->id);

    $this->actingAs($this->admin)
        ->get(route('roles.edit', $foreignRole))
        ->assertNotFound();
});

it('renders the permission reference page', function () {
    $this->actingAs($this->admin)
        ->get(route('permissions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('permissions/index')
            ->has('permissionGroups'),
        );
});

it('blocks users without setting.manage', function () {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($employee)
        ->get(route('roles.index'))
        ->assertForbidden();
});
