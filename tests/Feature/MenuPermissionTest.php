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
});

it('shares the full permission set for the admin', function () {
    $admin = User::where('email', 'admin@avanahr.id')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', fn ($perms): bool => collect($perms)->contains('setting.manage')
                && collect($perms)->contains('payroll.run')
                && collect($perms)->contains('employee.view'),
            ),
        );
});

it('shares only granted permissions for a plain employee', function () {
    $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $staff->assignRole('employee');

    $this->actingAs($staff)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.permissions', fn ($perms): bool => collect($perms)->contains('leave.view')
                && ! collect($perms)->contains('payroll.run')
                && ! collect($perms)->contains('setting.manage'),
            ),
        );
});
