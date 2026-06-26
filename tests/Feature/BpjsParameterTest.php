<?php

use App\Models\BpjsParameter;
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

function bpjsPayload(array $overrides = []): array
{
    return array_merge([
        'effective_date' => '2025-01-01',
        'kes_rate_employee' => 1.0, 'kes_rate_employer' => 4.0, 'kes_cap' => 12_000_000,
        'jht_employee' => 2.0, 'jht_employer' => 3.7, 'jkk' => 0.24, 'jkm' => 0.30,
        'jp_employee' => 1.0, 'jp_employer' => 2.0, 'jp_cap' => 10_042_300,
    ], $overrides);
}

it('renders the BPJS parameter page', function () {
    $this->actingAs($this->admin)
        ->get(route('bpjs-parameters.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bpjs-parameters/index')
            ->has('parameters')
            ->has('defaults'),
        );
});

it('creates a parameter and assembles tk_rates', function () {
    $this->actingAs($this->admin)
        ->post(route('bpjs-parameters.store'), bpjsPayload(['effective_date' => '2025-06-01']))
        ->assertRedirect();

    $param = BpjsParameter::orderByDesc('id')->firstOrFail();
    expect($param->effective_date->format('Y-m-d'))->toBe('2025-06-01');
    expect((float) $param->kes_rate_employer)->toBe(4.0);
    expect((float) $param->tk_rates['jht_employee'])->toBe(2.0);
    expect((int) $param->tk_rates['jp_cap'])->toBe(10_042_300);
});

it('validates required fields', function () {
    $this->actingAs($this->admin)
        ->post(route('bpjs-parameters.store'), ['effective_date' => ''])
        ->assertSessionHasErrors(['effective_date', 'kes_rate_employee', 'jp_cap']);
});

it('deletes a parameter', function () {
    $param = BpjsParameter::create([
        'effective_date' => '2025-09-01', 'kes_rate_employee' => 1, 'kes_rate_employer' => 4,
        'kes_cap' => 12_000_000, 'tk_rates' => ['jht_employee' => 2],
    ]);

    $this->actingAs($this->admin)
        ->delete(route('bpjs-parameters.destroy', $param))
        ->assertRedirect();

    $this->assertDatabaseMissing('bpjs_parameters', ['id' => $param->id]);
});

it('forbids users without payroll.view', function () {
    $plain = User::query()->where('tenant_id', $this->tenant->id)->get()
        ->first(fn (User $u) => ! $u->can('payroll.view'));

    $this->actingAs($plain)->get(route('bpjs-parameters.index'))->assertForbidden();
});
