<?php

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

it('renders the org structure tree', function () {
    $this->actingAs($this->admin)
        ->get(route('organization.structure'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('organization/structure')
            ->has('tree', 1)
            ->where('tree.0.type', 'company')
            ->has('tree.0.children'),
        );
});

it('forbids users without the employee permission', function () {
    $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $staff->assignRole('employee');

    $this->actingAs($staff)
        ->get(route('organization.structure'))
        ->assertForbidden();
});
