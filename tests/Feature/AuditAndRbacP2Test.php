<?php

use App\Models\AuditLog;
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

    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail(); // hr-admin (audit.view)
    $this->manager = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'manager'))->firstOrFail();
});

it('finance can now view the employee list', function () {
    $finance = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $finance->assignRole('finance');

    $this->actingAs($finance)
        ->get(route('employees.index'))
        ->assertOk();
});

it('lets a user with audit.view open the tenant audit log', function () {
    AuditLog::create([
        'tenant_id' => $this->tenant->id, 'user_id' => $this->admin->id,
        'auditable_type' => User::class, 'auditable_id' => $this->admin->id,
        'event' => 'updated', 'old_values' => ['x' => 1], 'new_values' => ['x' => 2], 'ip' => '127.0.0.1',
    ]);

    $this->actingAs($this->admin)
        ->get(route('audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('audit-logs/index')
            ->where('logs.data', fn ($rows) => collect($rows)->contains(fn ($r) => $r['ip'] === '127.0.0.1'))
        );
});

it('forbids the audit log for users without audit.view', function () {
    $this->actingAs($this->manager)
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

it('never shows another tenant audit log', function () {
    $tenant2 = Tenant::factory()->create();
    AuditLog::create([
        'tenant_id' => $tenant2->id, 'user_id' => null,
        'auditable_type' => User::class, 'auditable_id' => 1,
        'event' => 'deleted', 'old_values' => [], 'new_values' => [], 'ip' => '10.0.0.1',
    ]);

    $this->actingAs($this->admin)
        ->get(route('audit-logs.index'))
        ->assertInertia(fn ($page) => $page->where(
            'logs.data',
            fn ($rows) => collect($rows)->every(fn ($r) => $r['ip'] !== '10.0.0.1'), // tenant 2 log never leaks
        ));
});
