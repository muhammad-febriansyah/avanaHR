<?php

use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

    $this->superadmin = User::factory()->create(['is_super_admin' => true, 'tenant_id' => null]);
    $this->tenantUser = User::where('email', 'admin@avanahr.id')->firstOrFail();
});

it('changes a tenant subscription tier', function () {
    $this->actingAs($this->superadmin)
        ->put(route('platform.subscriptions.update', $this->tenant), ['tier' => 'enterprise'])
        ->assertRedirect();

    expect(TenantSubscription::where('tenant_id', $this->tenant->id)->first()->tier->value)->toBe('enterprise');
});

it('rejects an invalid subscription tier', function () {
    $this->actingAs($this->superadmin)
        ->put(route('platform.subscriptions.update', $this->tenant), ['tier' => 'galaxy'])
        ->assertSessionHasErrors('tier');
});

it('blocks subscription change for non super-admins', function () {
    $this->actingAs($this->tenantUser)
        ->put(route('platform.subscriptions.update', $this->tenant), ['tier' => 'enterprise'])
        ->assertForbidden();
});

it('lets a super-admin impersonate a tenant user and return', function () {
    // Start impersonation → now acting as a tenant-1 admin.
    $this->actingAs($this->superadmin)
        ->post(route('platform.tenants.impersonate', $this->tenant))
        ->assertRedirect(route('dashboard'));

    expect((int) Auth::user()->tenant_id)->toBe($this->tenant->id)
        ->and(Auth::id())->not->toBe($this->superadmin->id)
        ->and(session()->has('impersonator_id'))->toBeTrue();

    // Stop → back to the super-admin.
    $this->post(route('impersonate.stop'))->assertRedirect();

    expect(Auth::id())->toBe($this->superadmin->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

it('blocks impersonation for non super-admins', function () {
    $this->actingAs($this->tenantUser)
        ->post(route('platform.tenants.impersonate', $this->tenant))
        ->assertForbidden();
});
