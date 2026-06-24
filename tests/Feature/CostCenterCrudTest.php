<?php

use App\Models\CostCenter;
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

function validCostCenterPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'CC900',
        'name' => 'Operasional HQ',
    ], $overrides);
}

it('renders the cost centers index with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('cost-centers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cost-centers/index')
            ->has('costCenters')
            ->has('breadcrumbs', 2),
        );
});

it('renders the create page with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('cost-centers.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cost-centers/create')
            ->has('breadcrumbs', 3),
        );
});

it('creates a cost center', function () {
    $this->actingAs($this->admin)
        ->post(route('cost-centers.store'), validCostCenterPayload())
        ->assertRedirect(route('cost-centers.index'));

    $this->assertDatabaseHas('cost_centers', [
        'tenant_id' => $this->tenant->id,
        'code' => 'CC900',
        'name' => 'Operasional HQ',
    ]);
});

it('requires code and name', function () {
    $this->actingAs($this->admin)
        ->post(route('cost-centers.store'), validCostCenterPayload(['code' => '', 'name' => '']))
        ->assertSessionHasErrors(['code', 'name']);
});

it('rejects a duplicate code within the tenant', function () {
    CostCenter::factory()->create(['code' => 'CC900']);

    $this->actingAs($this->admin)
        ->post(route('cost-centers.store'), validCostCenterPayload(['code' => 'CC900']))
        ->assertSessionHasErrors('code');
});

it('updates a cost center', function () {
    $costCenter = CostCenter::factory()->create(['code' => 'CC901', 'name' => 'Lama']);

    $this->actingAs($this->admin)
        ->put(route('cost-centers.update', $costCenter), validCostCenterPayload([
            'code' => 'CC901',
            'name' => 'Baru',
        ]))
        ->assertRedirect(route('cost-centers.index'));

    expect($costCenter->fresh()->name)->toBe('Baru');
});

it('deletes a cost center', function () {
    $costCenter = CostCenter::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('cost-centers.destroy', $costCenter))
        ->assertRedirect();

    $this->assertSoftDeleted('cost_centers', ['id' => $costCenter->id]);
});

it('blocks users without employee permission', function () {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($employee)
        ->get(route('cost-centers.index'))
        ->assertForbidden();
});
