<?php

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Support\CurrentTenant;
use App\Support\Security\PasswordPolicy;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
});

/**
 * Persist a partial security policy override for the current tenant.
 *
 * @param  array<string, int|bool>  $override
 */
function storeSecurityPolicy(array $override): void
{
    TenantSetting::query()->updateOrCreate(
        ['key' => 'security'],
        ['value' => $override, 'type' => 'security'],
    );
}

test('password policy rule reflects tenant configuration', function () {
    storeSecurityPolicy([
        'password_min_length' => 10,
        'password_require_uppercase' => true,
        'password_require_number' => true,
        'password_require_symbol' => true,
    ]);

    $rule = PasswordPolicy::rules();

    $weak = Validator::make(['password' => 'Password1'], ['password' => $rule]);
    expect($weak->fails())->toBeTrue();

    $strong = Validator::make(['password' => 'Password1!xyz'], ['password' => $rule]);
    expect($strong->passes())->toBeTrue();
});

test('password policy falls back to minimum rule without tenant context', function () {
    app(CurrentTenant::class)->forget();

    $rule = PasswordPolicy::rules();

    expect(Validator::make(['password' => 'password'], ['password' => $rule])->passes())->toBeTrue();
    expect(Validator::make(['password' => 'short'], ['password' => $rule])->fails())->toBeTrue();
});

test('idle session beyond timeout redirects authenticated request to login', function () {
    storeSecurityPolicy(['session_timeout_minutes' => 1]);

    $response = $this->actingAs($this->admin)
        ->withSession(['last_activity' => now()->subMinutes(30)->timestamp])
        ->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('active session within timeout is not logged out', function () {
    storeSecurityPolicy(['session_timeout_minutes' => 120]);

    $response = $this->actingAs($this->admin)
        ->withSession(['last_activity' => now()->subMinutes(5)->timestamp])
        ->get(route('dashboard'));

    $response->assertOk();
    $this->assertAuthenticated();
});

test('enforce 2fa redirects user without two factor to security settings', function () {
    storeSecurityPolicy(['enforce_2fa' => true]);

    $response = $this->actingAs($this->admin)
        ->withSession(['last_activity' => now()->timestamp])
        ->get(route('dashboard'));

    $response->assertRedirect(route('security.edit'));
    $this->assertAuthenticated();
});
