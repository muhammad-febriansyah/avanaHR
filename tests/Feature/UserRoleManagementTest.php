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

it('renders the users index with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/index')
            ->has('users')
            ->has('breadcrumbs', 2),
        );
});

it('excludes super admins from the list', function () {
    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('users', fn ($users) => collect($users)->every(
                fn ($u) => $u['email'] !== 'superadmin@avanahr.id'
            )),
        );
});

it('renders the edit page with assignable tenant roles', function () {
    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($this->admin)
        ->get(route('users.edit', $user))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/edit')
            ->has('roles')
            ->where('isSelf', false)
            ->has('breadcrumbs', 3),
        );
});

it('assigns roles to a user', function () {
    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), ['roles' => ['hr-admin', 'manager']])
        ->assertRedirect(route('users.index'));

    $user->setRelation('roles', $user->roles()->get());
    expect($user->fresh()->getRoleNames()->sort()->values()->all())
        ->toBe(['hr-admin', 'manager']);
});

it('replaces existing roles on update', function () {
    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->assignRole('employee');

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), ['roles' => ['manager']])
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->getRoleNames()->all())->toBe(['manager']);
});

it('clears roles when none selected', function () {
    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->assignRole('employee');

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), ['roles' => []])
        ->assertRedirect(route('users.index'));

    expect($user->fresh()->getRoleNames()->all())->toBe([]);
});

it('rejects a role from another tenant', function () {
    $otherTenant = Tenant::factory()->create();
    Role::create(['name' => 'foreign-role', 'guard_name' => 'web', 'team_id' => $otherTenant->id]);

    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), ['roles' => ['foreign-role']])
        ->assertSessionHasErrors('roles.0');
});

it('prevents a user from changing their own roles', function () {
    $before = $this->admin->getRoleNames()->sort()->values()->all();

    $this->actingAs($this->admin)
        ->put(route('users.update', $this->admin), ['roles' => ['employee']])
        ->assertRedirect();

    expect($this->admin->fresh()->getRoleNames()->sort()->values()->all())->toBe($before);
});

it('cannot manage a super admin', function () {
    $superAdmin = User::factory()->create(['tenant_id' => null, 'is_super_admin' => true]);

    $this->actingAs($this->admin)
        ->get(route('users.edit', $superAdmin))
        ->assertNotFound();
});

it('cannot manage a user from another tenant', function () {
    $otherTenant = Tenant::factory()->create();
    $foreignUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

    $this->actingAs($this->admin)
        ->get(route('users.edit', $foreignUser))
        ->assertNotFound();
});

it('blocks users without setting.manage', function () {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($employee)
        ->get(route('users.index'))
        ->assertForbidden();
});
