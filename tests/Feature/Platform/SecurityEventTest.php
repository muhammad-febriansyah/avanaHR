<?php

use App\Models\SecurityEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create([
        'tenant_id' => null,
        'is_super_admin' => true,
    ]);
});

it('lists security events across tenants for the super-admin', function () {
    $tenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($tenant);

    SecurityEvent::factory()->count(4)->create();
    app(CurrentTenant::class)->forget();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.security-events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/security-events/index')
            ->has('events.data', 4)
            ->has('types'),
        );
});

it('exposes meta key/value pairs', function () {
    $tenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($tenant);

    SecurityEvent::factory()->create([
        'type' => 'login_failed',
        'meta' => ['attempts' => 3],
    ]);
    app(CurrentTenant::class)->forget();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.security-events.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.meta.0.key', 'attempts')
            ->where('events.data.0.meta.0.value', '3'),
        );
});

it('filters security events by type', function () {
    $tenant = Tenant::factory()->create();
    app(CurrentTenant::class)->set($tenant);

    SecurityEvent::factory()->type('login_success')->create();
    SecurityEvent::factory()->type('locked_out')->create();
    app(CurrentTenant::class)->forget();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.security-events.index', ['type' => 'locked_out']))
        ->assertInertia(fn (Assert $page) => $page->has('events.data', 1));
});

it('forbids non super-admins from security events', function () {
    $this->seed(DemoTenantSeeder::class);
    $admin = User::where('email', 'admin@avanahr.id')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('platform.security-events.index'))
        ->assertForbidden();
});

it('seeds demo security events', function () {
    $this->seed(DemoTenantSeeder::class);

    expect(SecurityEvent::withoutGlobalScopes()->count())->toBeGreaterThan(0);
});
