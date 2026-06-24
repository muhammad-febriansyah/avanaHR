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

it('shares only the permissions granted by the user role to the frontend', function () {
    $role = Role::create(['name' => 'kasir', 'guard_name' => 'web', 'team_id' => $this->tenant->id]);
    $role->syncPermissions(['employee.view']);

    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('employee.view')
                && ! collect($permissions)->contains('report.view')
            ),
        );
});

it('reflects a role permission change in the shared sidebar permissions', function () {
    $role = Role::create(['name' => 'kasir', 'guard_name' => 'web', 'team_id' => $this->tenant->id]);
    $role->syncPermissions(['employee.view']);

    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->assignRole($role);

    // Admin grants report.view through the Role menu (the page we built).
    $this->actingAs($this->admin)
        ->put(route('roles.update', $role), [
            'name' => 'kasir',
            'permissions' => ['employee.view', 'report.view'],
        ])
        ->assertRedirect(route('roles.index'));

    // The user's sidebar permissions now include the newly granted report.view.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('employee.view')
                && collect($permissions)->contains('report.view')
            ),
        );
});

it('removes a permission from the sidebar when revoked from the role', function () {
    $role = Role::create(['name' => 'kasir', 'guard_name' => 'web', 'team_id' => $this->tenant->id]);
    $role->syncPermissions(['employee.view', 'report.view']);

    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->assignRole($role);

    $this->actingAs($this->admin)
        ->put(route('roles.update', $role), [
            'name' => 'kasir',
            'permissions' => ['employee.view'],
        ])
        ->assertRedirect(route('roles.index'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', fn ($permissions) => ! collect($permissions)->contains('report.view')),
        );
});
