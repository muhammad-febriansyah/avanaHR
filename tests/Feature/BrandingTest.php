<?php

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
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

it('renders the branding page', function () {
    $this->actingAs($this->admin)
        ->get(route('branding.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branding/edit')
            ->has('company.name'),
        );
});

it('updates the company name', function () {
    $this->actingAs($this->admin)
        ->post(route('branding.update'), ['name' => 'PT Avana Sejahtera'])
        ->assertRedirect();

    expect(Company::orderBy('id')->first()->name)->toBe('PT Avana Sejahtera');
});

it('uploads a logo to a tenant-scoped path', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post(route('branding.update'), [
            'name' => 'PT Avana Indonesia',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])
        ->assertRedirect();

    $company = Company::orderBy('id')->first();
    expect($company->logo_path)->not->toBeNull();
    expect($company->logo_path)->toStartWith('logos/'.$company->tenant_id.'/');
    Storage::disk('public')->assertExists($company->logo_path);
});

it('rejects a non-image logo', function () {
    $this->actingAs($this->admin)
        ->post(route('branding.update'), [
            'name' => 'PT X',
            'logo' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('logo');
});

it('forbids users without setting.manage', function () {
    $plain = User::query()
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('setting.manage'));

    $this->actingAs($plain)->get(route('branding.edit'))->assertForbidden();
});
