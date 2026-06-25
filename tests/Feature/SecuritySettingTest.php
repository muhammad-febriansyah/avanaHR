<?php

use App\Models\Tenant;
use App\Models\TenantSetting;
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

function securityPayload(array $overrides = []): array
{
    return array_merge([
        'password_min_length' => 10,
        'password_require_uppercase' => true,
        'password_require_number' => true,
        'password_require_symbol' => true,
        'password_expiry_days' => 90,
        'session_timeout_minutes' => 60,
        'max_login_attempts' => 5,
        'lockout_minutes' => 30,
        'enforce_2fa' => true,
    ], $overrides);
}

it('renders the security settings with defaults', function () {
    $this->actingAs($this->admin)
        ->get(route('security-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('security-settings/edit')
            ->where('settings.password_min_length', 8)
            ->where('settings.enforce_2fa', false),
        );
});

it('saves the security policy to tenant settings', function () {
    $this->actingAs($this->admin)
        ->put(route('security-settings.update'), securityPayload())
        ->assertRedirect();

    $setting = TenantSetting::where('key', 'security')->firstOrFail();

    expect($setting->value['password_min_length'])->toBe(10);
    expect($setting->value['enforce_2fa'])->toBeTrue();
    expect($setting->value['password_expiry_days'])->toBe(90);
});

it('reflects stored settings on edit', function () {
    TenantSetting::create([
        'tenant_id' => $this->tenant->id,
        'key' => 'security',
        'type' => 'security',
        'value' => securityPayload(['password_min_length' => 16]),
    ]);

    $this->actingAs($this->admin)
        ->get(route('security-settings.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('settings.password_min_length', 16),
        );
});

it('validates the minimum password length', function () {
    $this->actingAs($this->admin)
        ->put(route('security-settings.update'), securityPayload(['password_min_length' => 3]))
        ->assertSessionHasErrors('password_min_length');
});

it('forbids users without setting.manage', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('setting.manage'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('security-settings.edit'))
        ->assertForbidden();
});
