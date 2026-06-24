<?php

use App\Models\JobGrade;
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

function validJobGradePayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'GR90',
        'name' => 'Grade Z',
        'salary_band_min' => 5_000_000,
        'salary_band_max' => 10_000_000,
    ], $overrides);
}

it('renders the job grades index with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('job-grades.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('job-grades/index')
            ->has('jobGrades')
            ->has('breadcrumbs', 2),
        );
});

it('renders the create page with breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('job-grades.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('job-grades/create')
            ->has('breadcrumbs', 3),
        );
});

it('creates a job grade', function () {
    $this->actingAs($this->admin)
        ->post(route('job-grades.store'), validJobGradePayload())
        ->assertRedirect(route('job-grades.index'));

    $this->assertDatabaseHas('job_grades', [
        'tenant_id' => $this->tenant->id,
        'code' => 'GR90',
        'salary_band_min' => 5_000_000,
        'salary_band_max' => 10_000_000,
    ]);
});

it('requires code, name and salary band', function () {
    $this->actingAs($this->admin)
        ->post(route('job-grades.store'), validJobGradePayload([
            'code' => '', 'name' => '', 'salary_band_min' => '', 'salary_band_max' => '',
        ]))
        ->assertSessionHasErrors(['code', 'name', 'salary_band_min', 'salary_band_max']);
});

it('rejects a max band lower than the min band', function () {
    $this->actingAs($this->admin)
        ->post(route('job-grades.store'), validJobGradePayload([
            'salary_band_min' => 10_000_000,
            'salary_band_max' => 5_000_000,
        ]))
        ->assertSessionHasErrors('salary_band_max');
});

it('rejects a duplicate code within the tenant', function () {
    JobGrade::factory()->create(['code' => 'GR90']);

    $this->actingAs($this->admin)
        ->post(route('job-grades.store'), validJobGradePayload(['code' => 'GR90']))
        ->assertSessionHasErrors('code');
});

it('updates a job grade', function () {
    $jobGrade = JobGrade::factory()->create(['code' => 'GR91', 'name' => 'Lama']);

    $this->actingAs($this->admin)
        ->put(route('job-grades.update', $jobGrade), validJobGradePayload([
            'code' => 'GR91',
            'name' => 'Baru',
        ]))
        ->assertRedirect(route('job-grades.index'));

    expect($jobGrade->fresh()->name)->toBe('Baru');
});

it('deletes an unused job grade', function () {
    $jobGrade = JobGrade::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('job-grades.destroy', $jobGrade))
        ->assertRedirect();

    $this->assertSoftDeleted('job_grades', ['id' => $jobGrade->id]);
});

it('blocks deleting a job grade used by a position', function () {
    $jobGrade = JobGrade::firstOrFail();
    Position::factory()->create(['job_grade_id' => $jobGrade->id]);

    $this->actingAs($this->admin)
        ->delete(route('job-grades.destroy', $jobGrade))
        ->assertRedirect();

    $this->assertDatabaseHas('job_grades', ['id' => $jobGrade->id, 'deleted_at' => null]);
});

it('blocks users without employee permission', function () {
    $employee = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($employee)
        ->get(route('job-grades.index'))
        ->assertForbidden();
});
