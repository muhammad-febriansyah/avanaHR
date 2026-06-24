<?php

use App\Models\Company;
use App\Models\Tenant;
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

function validCompanyPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'CMP900',
        'name' => 'PT Avana Test',
        'npwp' => '01.234.567.8-901.000',
        'address' => 'Jl. Test No. 1',
        'phone' => '021-1234567',
        'email' => 'test@avana.id',
    ], $overrides);
}

it('renders the companies index with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('companies.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/index')
            ->has('companies')
            ->has('breadcrumbs', 2),
        );
});

it('renders the create page', function () {
    $this->actingAs($this->admin)
        ->get(route('companies.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/create')
            ->has('breadcrumbs', 3),
        );
});

it('creates a company', function () {
    $this->actingAs($this->admin)
        ->post(route('companies.store'), validCompanyPayload())
        ->assertRedirect(route('companies.index'));

    $this->assertDatabaseHas('companies', [
        'tenant_id' => $this->tenant->id,
        'code' => 'CMP900',
        'name' => 'PT Avana Test',
    ]);
});

it('requires code and name', function () {
    $this->actingAs($this->admin)
        ->post(route('companies.store'), validCompanyPayload(['code' => '', 'name' => '']))
        ->assertSessionHasErrors(['code', 'name']);
});

it('rejects a duplicate code within the tenant', function () {
    Company::factory()->create(['code' => 'CMP900']);

    $this->actingAs($this->admin)
        ->post(route('companies.store'), validCompanyPayload(['code' => 'CMP900']))
        ->assertSessionHasErrors('code');
});

it('updates a company', function () {
    $company = Company::factory()->create(['code' => 'CMP901', 'name' => 'Lama']);

    $this->actingAs($this->admin)
        ->put(route('companies.update', $company), validCompanyPayload([
            'code' => 'CMP901',
            'name' => 'Baru',
        ]))
        ->assertRedirect(route('companies.index'));

    expect($company->fresh()->name)->toBe('Baru');
});

it('deletes a company without dependents', function () {
    $company = Company::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('companies.destroy', $company))
        ->assertRedirect();

    $this->assertSoftDeleted('companies', ['id' => $company->id]);
});

it('blocks users without setting.manage', function () {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($employee)
        ->get(route('companies.index'))
        ->assertForbidden();
});
