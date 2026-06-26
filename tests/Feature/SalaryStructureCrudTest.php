<?php

use App\Models\JobGrade;
use App\Models\SalaryStructure;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DemoTenantSeeder::class);
    $this->tenant = Tenant::firstOrFail();
    app(CurrentTenant::class)->set($this->tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->employeeUser = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'employee'))->firstOrFail();
    $this->grade = JobGrade::firstOrFail();
});

it('creates a salary structure for a grade', function () {
    $this->actingAs($this->admin)
        ->post(route('salary-structures.store'), [
            'job_grade_id' => $this->grade->id, 'band_min' => 8_000_000, 'band_max' => 15_000_000,
        ])->assertRedirect();

    expect(SalaryStructure::where('job_grade_id', $this->grade->id)->where('band_min', 8_000_000)->where('band_max', 15_000_000)->exists())->toBeTrue();
});

it('rejects a second structure for the same grade', function () {
    SalaryStructure::factory()->create([
        'tenant_id' => $this->tenant->id, 'job_grade_id' => $this->grade->id,
        'band_min' => 8_000_000, 'band_max' => 15_000_000,
    ]);

    $this->actingAs($this->admin)
        ->post(route('salary-structures.store'), [
            'job_grade_id' => $this->grade->id, 'band_min' => 9_000_000, 'band_max' => 16_000_000,
        ])->assertSessionHasErrors('job_grade_id');

    expect(SalaryStructure::where('job_grade_id', $this->grade->id)->count())->toBe(1);
});

it('rejects a band where max is below min', function () {
    $this->actingAs($this->admin)
        ->post(route('salary-structures.store'), [
            'job_grade_id' => $this->grade->id, 'band_min' => 15_000_000, 'band_max' => 8_000_000,
        ])->assertSessionHasErrors('band_max');
});

it('updates the band of a structure', function () {
    $structure = SalaryStructure::factory()->create([
        'tenant_id' => $this->tenant->id, 'job_grade_id' => $this->grade->id,
        'band_min' => 8_000_000, 'band_max' => 15_000_000,
    ]);

    $this->actingAs($this->admin)
        ->put(route('salary-structures.update', $structure), ['band_min' => 9_000_000, 'band_max' => 18_000_000])
        ->assertRedirect();

    expect((int) $structure->fresh()->band_max)->toBe(18_000_000);
});

it('deletes a structure', function () {
    $structure = SalaryStructure::factory()->create([
        'tenant_id' => $this->tenant->id, 'job_grade_id' => $this->grade->id,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('salary-structures.destroy', $structure))
        ->assertRedirect();

    expect(SalaryStructure::find($structure->id))->toBeNull();
});

it('forbids the page for users without payroll permission', function () {
    $this->actingAs($this->employeeUser)->get(route('salary-structures.index'))->assertForbidden();
});
