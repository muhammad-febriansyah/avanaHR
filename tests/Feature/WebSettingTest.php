<?php

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use App\Support\SiteSettings;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('renders the web settings page with defaults', function () {
    $this->actingAs($this->admin)
        ->get(route('web-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/web')
            ->where('settings.site_name', 'AvanaHR')
            ->has('settings.social.facebook'),
        );
});

it('saves web settings and social links', function () {
    $this->actingAs($this->admin)
        ->post(route('web-settings.update'), [
            'site_name' => 'AvanaHR',
            'tagline' => 'Advancing People, Empowering Growth',
            'meta_keywords' => 'HRIS, payroll, absensi',
            'contact_email' => 'support@avanahr.co.id',
            'social' => [
                'instagram' => 'https://instagram.com/avanahr.id',
                'linkedin' => 'https://linkedin.com/company/avanahr',
            ],
        ])
        ->assertRedirect();

    $value = Setting::where('key', SiteSettings::KEY)->value('value');
    expect($value['meta_keywords'])->toBe('HRIS, payroll, absensi');
    expect($value['social']['instagram'])->toBe('https://instagram.com/avanahr.id');
});

it('uploads logo and favicon to the site path', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('web-settings.update'), [
            'site_name' => 'AvanaHR',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
            'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
        ])
        ->assertRedirect();

    $value = Setting::where('key', SiteSettings::KEY)->value('value');
    expect($value['logo_path'])->toStartWith('site/');
    expect($value['favicon_path'])->toStartWith('site/');
    Storage::disk('public')->assertExists($value['logo_path']);
    Storage::disk('public')->assertExists($value['favicon_path']);
});

it('rejects an invalid social url', function () {
    $this->actingAs($this->admin)
        ->post(route('web-settings.update'), [
            'site_name' => 'AvanaHR',
            'social' => ['facebook' => 'not-a-url'],
        ])
        ->assertSessionHasErrors('social.facebook');
});

it('requires the site name', function () {
    $this->actingAs($this->admin)
        ->post(route('web-settings.update'), ['site_name' => ''])
        ->assertSessionHasErrors('site_name');
});

it('forbids users without setting.manage', function () {
    $staff = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $staff->assignRole('employee');

    $this->actingAs($staff)
        ->get(route('web-settings.edit'))
        ->assertForbidden();
});
