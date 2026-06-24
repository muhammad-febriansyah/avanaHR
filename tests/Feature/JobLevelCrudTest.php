<?php

use App\Models\JobLevel;
use App\Models\Position;
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

function validJobLevelPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'LV90',
        'name' => 'Staff',
        'order' => 1,
    ], $overrides);
}

it('renders the job levels index with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('job-levels.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('job-levels/index')
            ->has('jobLevels')
            ->has('breadcrumbs', 2),
        );
});

it('renders the create page with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('job-levels.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('job-levels/create')
            ->has('breadcrumbs', 3),
        );
});

it('creates a job level', function () {
    $this->actingAs($this->admin)
        ->post(route('job-levels.store'), validJobLevelPayload())
        ->assertRedirect(route('job-levels.index'));

    $this->assertDatabaseHas('job_levels', [
        'tenant_id' => $this->tenant->id,
        'code' => 'LV90',
        'name' => 'Staff',
        'order' => 1,
    ]);
});

it('requires code and name', function () {
    $this->actingAs($this->admin)
        ->post(route('job-levels.store'), validJobLevelPayload(['code' => '', 'name' => '']))
        ->assertSessionHasErrors(['code', 'name']);
});

it('rejects a duplicate code within the tenant', function () {
    JobLevel::factory()->create(['code' => 'LV90']);

    $this->actingAs($this->admin)
        ->post(route('job-levels.store'), validJobLevelPayload(['code' => 'LV90']))
        ->assertSessionHasErrors('code');
});

it('updates a job level', function () {
    $jobLevel = JobLevel::factory()->create(['code' => 'LV91', 'name' => 'Lama']);

    $this->actingAs($this->admin)
        ->put(route('job-levels.update', $jobLevel), validJobLevelPayload([
            'code' => 'LV91',
            'name' => 'Baru',
        ]))
        ->assertRedirect(route('job-levels.index'));

    expect($jobLevel->fresh()->name)->toBe('Baru');
});

it('deletes an unused job level', function () {
    $jobLevel = JobLevel::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('job-levels.destroy', $jobLevel))
        ->assertRedirect();

    $this->assertSoftDeleted('job_levels', ['id' => $jobLevel->id]);
});

it('blocks deleting a job level still used by a position', function () {
    $jobLevel = JobLevel::firstOrFail();
    Position::factory()->create(['job_level_id' => $jobLevel->id]);

    $this->actingAs($this->admin)
        ->delete(route('job-levels.destroy', $jobLevel))
        ->assertRedirect();

    $this->assertDatabaseHas('job_levels', ['id' => $jobLevel->id, 'deleted_at' => null]);
});

it('blocks users without employee permission', function () {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($employee)
        ->get(route('job-levels.index'))
        ->assertForbidden();
});
