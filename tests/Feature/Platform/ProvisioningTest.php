<?php

use App\Models\Tenant;
use App\Models\TenantProvision;
use App\Models\User;
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

it('lists tenants with provisioning status', function () {
    Tenant::factory()->count(2)->create();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.provisioning.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/provisioning/index')
            ->has('tenants.data', 2)
            ->where('tenants.data.0.status', 'pending'),
        );
});

it('reflects an existing provision record', function () {
    $tenant = Tenant::factory()->create();
    TenantProvision::factory()->completed()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($this->superAdmin)
        ->get(route('platform.provisioning.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tenants.data.0.status', 'completed')
            ->where('tenants.data.0.config_applied', true),
        );
});

it('applies the default config for a tenant', function () {
    $tenant = Tenant::factory()->create();

    $this->actingAs($this->superAdmin)
        ->post(route('platform.provisioning.apply', $tenant))
        ->assertRedirect();

    $provision = TenantProvision::where('tenant_id', $tenant->id)->firstOrFail();

    expect($provision->default_config_applied)->toBeTrue();
    expect($provision->status)->toBe('completed');
});

it('does not re-apply an already provisioned tenant', function () {
    $tenant = Tenant::factory()->create();
    TenantProvision::factory()->completed()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($this->superAdmin)
        ->post(route('platform.provisioning.apply', $tenant))
        ->assertRedirect();

    expect(TenantProvision::where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('forbids non super-admins from provisioning', function () {
    $this->seed(DemoTenantSeeder::class);
    $admin = User::where('email', 'admin@avanahr.id')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('platform.provisioning.index'))
        ->assertForbidden();
});
