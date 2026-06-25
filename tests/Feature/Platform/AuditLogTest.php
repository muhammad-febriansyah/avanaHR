<?php

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create([
        'tenant_id' => null,
        'is_super_admin' => true,
    ]);
});

it('lists audit logs across tenants for the super-admin', function () {
    $tenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

    AuditLog::factory()->count(3)->create();
    app(CurrentTenant::class)->forget();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/audit-logs/index')
            ->has('logs.data', 3)
            ->has('events'),
        );
});

it('exposes a field-level diff for changed attributes', function () {
    $tenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($tenant);

    AuditLog::factory()->create([
        'event' => 'updated',
        'old_values' => ['status' => 'probation'],
        'new_values' => ['status' => 'active'],
    ]);
    app(CurrentTenant::class)->forget();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.audit-logs.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('logs.data.0.changes.0.field', 'status')
            ->where('logs.data.0.changes.0.old', 'probation')
            ->where('logs.data.0.changes.0.new', 'active'),
        );
});

it('filters audit logs by event', function () {
    $tenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($tenant);

    AuditLog::factory()->event('created')->create();
    AuditLog::factory()->event('deleted')->create();
    app(CurrentTenant::class)->forget();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.audit-logs.index', ['event' => 'deleted']))
        ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1));
});

it('forbids non super-admins from the audit log', function () {
    $this->seed(DemoTenantSeeder::class);
    $admin = User::where('email', 'admin@avanahr.id')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('platform.audit-logs.index'))
        ->assertForbidden();
});

it('seeds demo audit trail entries', function () {
    $this->seed(DemoTenantSeeder::class);

    expect(AuditLog::withoutGlobalScopes()->count())->toBeGreaterThan(0);
});
