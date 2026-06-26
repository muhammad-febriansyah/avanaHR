<?php

use App\Approvals\ApprovalEngine;
use App\Enums\RequestStatus;
use App\Models\ApprovalFlow;
use App\Models\Employee;
use App\Models\OvertimeRequest;
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

    $this->engine = app(ApprovalEngine::class);
    $this->employee = Employee::firstOrFail();
    $this->admin = User::where('email', 'admin@avanahr.id')->firstOrFail();
    $this->manager = User::query()
        ->whereHas('roles', fn ($q) => $q->where('name', 'manager'))
        ->firstOrFail();
});

function managerOvertimeFlow(): void
{
    ApprovalFlow::where('transaction_type', 'overtime')->delete();
    $flow = ApprovalFlow::create([
        'transaction_type' => 'overtime',
        'name' => 'Lembur',
        'is_active' => true,
    ]);
    $flow->steps()->create([
        'order' => 1,
        'mode' => 'sequential',
        'approver_type' => 'role',
        'approver_ref' => 'manager',
        'min_approvals' => 1,
    ]);
}

it('renders the approval inbox without lazy-loading violation', function () {
    managerOvertimeFlow();

    $overtime = OvertimeRequest::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => RequestStatus::Pending,
    ]);
    $this->engine->submit($overtime, $this->admin);

    $this->actingAs($this->manager)
        ->get(route('approvals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('approvals/index')
            ->where('requests.0.type', 'overtime')
            ->where('requests.0.title', fn ($title) => str_contains((string) $title, 'Lembur'))
        );
});

it('renders an approval detail page without lazy-loading violation', function () {
    managerOvertimeFlow();

    $overtime = OvertimeRequest::factory()->create([
        'employee_id' => $this->employee->id,
        'status' => RequestStatus::Pending,
    ]);
    $request = $this->engine->submit($overtime, $this->admin);

    $this->actingAs($this->manager)
        ->get(route('approvals.show', $request))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('approvals/show'));
});
