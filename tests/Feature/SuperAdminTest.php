<?php

use App\Models\User;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('grants the platform super-admin access without tenant roles', function () {
    $this->seed(DemoTenantSeeder::class);

    $superAdmin = User::factory()->create([
        'tenant_id' => null,
        'is_super_admin' => true,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('employees.index'))
        ->assertOk();

    $this->actingAs($superAdmin)
        ->get(route('organization.structure'))
        ->assertOk();
});

it('returns 403 instead of 500 when the super-admin opens tenant-scoped role and user management', function () {
    $this->seed(DemoTenantSeeder::class);

    $superAdmin = User::factory()->create([
        'tenant_id' => null,
        'is_super_admin' => true,
    ]);

    $this->actingAs($superAdmin)
        ->get(route('roles.index'))
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->get(route('users.index'))
        ->assertForbidden();
});

it('does not grant access to a regular user without permission', function () {
    $this->seed(DemoTenantSeeder::class);

    $user = User::factory()->create([
        'tenant_id' => null,
        'is_super_admin' => false,
    ]);

    $this->actingAs($user)
        ->get(route('employees.index'))
        ->assertForbidden();
});
