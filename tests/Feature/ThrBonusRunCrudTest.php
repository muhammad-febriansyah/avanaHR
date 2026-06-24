<?php

use App\Models\Tenant;
use App\Models\ThrBonusRun;
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

function runPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'thr',
        'period_ref' => 'Idul Fitri 2026',
    ], $overrides);
}

it('renders the thr/bonus index', function () {
    $this->actingAs($this->admin)
        ->get(route('thr-bonus-runs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('thr-bonus-runs/index')
            ->has('runs')
            ->has('types')
            ->has('statuses'),
        );
});

it('creates a run as draft', function () {
    $this->actingAs($this->admin)
        ->post(route('thr-bonus-runs.store'), runPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('thr_bonus_runs', [
        'tenant_id' => $this->tenant->id,
        'type' => 'thr',
        'period_ref' => 'Idul Fitri 2026',
        'status' => 'draft',
    ]);
});

it('rejects an invalid type', function () {
    $this->actingAs($this->admin)
        ->post(route('thr-bonus-runs.store'), runPayload(['type' => 'gift']))
        ->assertSessionHasErrors('type');
});

it('updates a run status', function () {
    $run = ThrBonusRun::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('thr-bonus-runs.update', $run), runPayload([
            'type' => $run->type,
            'period_ref' => $run->period_ref,
            'status' => 'approved',
        ]))
        ->assertRedirect();

    expect($run->fresh()->status)->toBe('approved');
});

it('blocks deleting a non-draft run', function () {
    $run = ThrBonusRun::factory()->approved()->create();

    $this->actingAs($this->admin)
        ->delete(route('thr-bonus-runs.destroy', $run))
        ->assertRedirect();

    $this->assertDatabaseHas('thr_bonus_runs', ['id' => $run->id]);
});

it('deletes a draft run', function () {
    $run = ThrBonusRun::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('thr-bonus-runs.destroy', $run))
        ->assertRedirect();

    $this->assertDatabaseMissing('thr_bonus_runs', ['id' => $run->id]);
});

it('forbids users without payroll.run from creating runs', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('payroll.run'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('thr-bonus-runs.store'), runPayload())
        ->assertForbidden();
});
