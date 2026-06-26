<?php

use App\Approvals\ApprovalEngine;
use App\Enums\ApprovalActionType;
use App\Enums\ApprovalStatus;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkVisit;
use App\Models\WorkVisitReport;
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
    $this->employee = Employee::query()->first();
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
});

/**
 * Build a valid store payload for a work visit.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function workVisitPayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'destination' => 'Surabaya',
        'purpose' => 'Meeting klien',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'transport_mode' => 'pesawat',
        'estimated_cost' => 1500000,
        'notes' => null,
    ], $overrides);
}

/**
 * Create a work visit directly (model is guarded against id only) so tests
 * needing a persisted visit do not depend on a factory.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeWorkVisit(array $overrides = []): WorkVisit
{
    return WorkVisit::create(array_merge([
        'tenant_id' => test()->tenant->id,
        'employee_id' => test()->employee->id,
        'destination' => 'Surabaya',
        'purpose' => 'Meeting klien',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'transport_mode' => 'pesawat',
        'estimated_cost' => 1500000,
        'status' => 'pending',
    ], $overrides));
}

it('renders the index', function () {
    $this->actingAs($this->admin)
        ->get(route('work-visits.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('work-visits/index')
            ->has('workVisits')
            ->has('statuses'),
        );
});

it('renders the create page', function () {
    $this->actingAs($this->admin)
        ->get(route('work-visits.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('work-visits/create')
            ->has('employees')
            ->has('transportModes'),
        );
});

it('creates a pending work visit and opens an approval request', function () {
    $this->actingAs($this->admin)
        ->post(route('work-visits.store'), workVisitPayload())
        ->assertRedirect();

    $this->assertDatabaseHas('work_visits', [
        'destination' => 'Surabaya',
        'status' => 'pending',
        'employee_id' => $this->employee->id,
        'tenant_id' => $this->tenant->id,
    ]);

    $visit = WorkVisit::firstOrFail();

    $this->assertDatabaseHas('approval_requests', [
        'approvable_type' => $visit->getMorphClass(),
        'approvable_id' => $visit->id,
        'status' => 'pending',
    ]);
    expect($visit->pendingApprovalRequest())->not->toBeNull();
});

it('fails validation without required fields', function () {
    $this->actingAs($this->admin)
        ->post(route('work-visits.store'), [])
        ->assertSessionHasErrors(['employee_id', 'destination', 'purpose', 'start_date', 'end_date']);
});

it('rejects an end date before the start date', function () {
    $this->actingAs($this->admin)
        ->post(route('work-visits.store'), workVisitPayload([
            'start_date' => now()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]))
        ->assertSessionHasErrors(['end_date']);
});

it('approves a work visit through the approval engine', function () {
    $this->actingAs($this->admin)
        ->post(route('work-visits.store'), workVisitPayload())
        ->assertRedirect();

    $visit = WorkVisit::firstOrFail();
    $request = $visit->pendingApprovalRequest();

    expect($request)->not->toBeNull();
    expect($request->status)->toBe(ApprovalStatus::Pending);

    app(ApprovalEngine::class)->act($request, $this->manager, ApprovalActionType::Approve);

    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($visit->fresh()->status)->toBe(RequestStatus::Approved);
});

it('rejects a work visit through the approval engine', function () {
    $this->actingAs($this->admin)
        ->post(route('work-visits.store'), workVisitPayload())
        ->assertRedirect();

    $visit = WorkVisit::firstOrFail();
    $request = $visit->pendingApprovalRequest();

    app(ApprovalEngine::class)->act($request, $this->manager, ApprovalActionType::Reject, 'tidak perlu');

    expect($request->fresh()->status)->toBe(ApprovalStatus::Rejected);
    expect($visit->fresh()->status)->toBe(RequestStatus::Rejected);
});

it('auto-approves a work visit when no flow is configured', function () {
    ApprovalFlow::where('transaction_type', 'work_visit')->delete();

    $this->actingAs($this->admin)
        ->post(route('work-visits.store'), workVisitPayload())
        ->assertRedirect();

    $visit = WorkVisit::firstOrFail();

    expect($visit->status)->toBe(RequestStatus::Approved);
    expect($visit->pendingApprovalRequest())->toBeNull();
});

it('adds a visit report', function () {
    $visit = makeWorkVisit(['status' => 'approved']);

    $this->actingAs($this->admin)
        ->post(route('work-visits.reports.store', $visit), [
            'visited_at' => now()->toDateString(),
            'location' => 'Kantor Klien',
            'notes' => 'Diskusi requirement dan timeline.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('work_visit_reports', [
        'work_visit_id' => $visit->id,
        'location' => 'Kantor Klien',
    ]);
});

it('deletes a visit report', function () {
    $visit = makeWorkVisit(['status' => 'approved']);

    $report = WorkVisitReport::create([
        'tenant_id' => $this->tenant->id,
        'work_visit_id' => $visit->id,
        'visited_at' => now()->toDateString(),
        'location' => 'Kantor Klien',
        'notes' => 'Diskusi requirement.',
    ]);

    $this->actingAs($this->admin)
        ->delete(route('work-visits.reports.destroy', [$visit, $report]))
        ->assertRedirect();

    $this->assertDatabaseMissing('work_visit_reports', [
        'id' => $report->id,
    ]);
});

it('only allows editing pending visits', function () {
    $visit = makeWorkVisit(['status' => 'approved']);

    $this->actingAs($this->admin)
        ->get(route('work-visits.edit', $visit))
        ->assertRedirect();
});

it('forbids users without employee.update from creating a work visit', function () {
    $auditor = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $auditor->assignRole('auditor');

    expect($auditor->can('employee.update'))->toBeFalse();

    $this->actingAs($auditor)
        ->post(route('work-visits.store'), workVisitPayload())
        ->assertForbidden();

    $this->actingAs($auditor)
        ->get(route('work-visits.create'))
        ->assertForbidden();
});
