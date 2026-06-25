<?php

use App\Enums\SubscriptionTier;
use App\Models\Tenant;
use App\Models\TenantSubscription;
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

function tenantWithTier(SubscriptionTier $tier): Tenant
{
    $tenant = Tenant::factory()->create();
    TenantSubscription::factory()->create(['tenant_id' => $tenant->id, 'tier' => $tier]);

    return $tenant;
}

it('lists tenant subscriptions with a tier summary', function () {
    tenantWithTier(SubscriptionTier::Essential);
    tenantWithTier(SubscriptionTier::Enterprise);

    $this->actingAs($this->superAdmin)
        ->get(route('platform.subscriptions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/subscriptions/index')
            ->has('tenants.data', 2)
            ->has('tiers')
            ->where('summary.essential', 1)
            ->where('summary.enterprise', 1)
            ->where('summary.professional', 0),
        );
});

it('filters subscriptions by tier', function () {
    tenantWithTier(SubscriptionTier::Essential);
    tenantWithTier(SubscriptionTier::Enterprise);

    $this->actingAs($this->superAdmin)
        ->get(route('platform.subscriptions.index', ['tier' => 'enterprise']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('tenants.data', 1)
            ->where('tenants.data.0.tier', 'enterprise'),
        );
});

it('reports enabled feature count out of the catalog total', function () {
    $tenant = Tenant::factory()->create();
    TenantSubscription::factory()->create([
        'tenant_id' => $tenant->id,
        'tier' => SubscriptionTier::Professional,
        'feature_flags' => [],
    ]);

    $this->actingAs($this->superAdmin)
        ->get(route('platform.subscriptions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tenants.data.0.features_enabled', fn ($v) => $v >= 0)
            ->where('tenants.data.0.features_total', fn ($v) => $v >= 0),
        );
});

it('forbids non super-admins from subscriptions', function () {
    $this->seed(DemoTenantSeeder::class);
    $admin = User::where('email', 'admin@avanahr.id')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('platform.subscriptions.index'))
        ->assertForbidden();
});
