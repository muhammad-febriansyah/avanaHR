<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create([
        'tenant_id' => null,
        'is_super_admin' => true,
    ]);
});

function tenantPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'PT Contoh Sejahtera',
        'slug' => 'contoh-sejahtera',
        'status' => 'active',
        'tier' => 'professional',
    ], $overrides);
}

it('lists tenants for the super-admin', function () {
    Tenant::factory()->count(2)->create();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.tenants.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/tenants/index')
            ->has('tenants.data')
            ->has('tiers'),
        );
});

it('creates a tenant with a subscription', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('platform.tenants.store'), tenantPayload())
        ->assertRedirect(route('platform.tenants.index'));

    $tenant = Tenant::where('slug', 'contoh-sejahtera')->firstOrFail();

    $this->assertDatabaseHas('tenant_subscriptions', [
        'tenant_id' => $tenant->id,
        'tier' => 'professional',
    ]);
});

it('updates a tenant tier', function () {
    $tenant = Tenant::factory()->create();
    $tenant->subscriptions()->create(['tier' => 'essential', 'status' => 'active']);

    $this->actingAs($this->superAdmin)
        ->put(route('platform.tenants.update', $tenant), tenantPayload([
            'slug' => $tenant->slug,
            'tier' => 'enterprise',
        ]))
        ->assertRedirect(route('platform.tenants.index'));

    expect($tenant->fresh()->subscription->tier->value)->toBe('enterprise');
});

it('soft deletes a tenant', function () {
    $tenant = Tenant::factory()->create();

    $this->actingAs($this->superAdmin)
        ->delete(route('platform.tenants.destroy', $tenant))
        ->assertRedirect(route('platform.tenants.index'));

    $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
});

it('forbids non super-admin users', function () {
    $regular = User::factory()->create(['tenant_id' => null, 'is_super_admin' => false]);

    $this->actingAs($regular)
        ->get(route('platform.tenants.index'))
        ->assertForbidden();
});
