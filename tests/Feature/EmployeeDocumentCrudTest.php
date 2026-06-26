<?php

use App\Models\Employee;
use App\Models\EmployeeDocument;
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
    $this->employee = Employee::firstOrFail();
});

function documentPayload(array $overrides = []): array
{
    return array_merge([
        'employee_id' => test()->employee->id,
        'document_type' => 'ktp',
        'number' => '3174010101900001',
        'issued_at' => '2024-01-01',
        'expired_at' => '2029-01-01',
        'reminder_days' => 30,
        'access_level' => 'internal',
    ], $overrides);
}

it('renders the document index', function () {
    $this->actingAs($this->admin)
        ->get(route('employee-documents.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employee-documents/index')
            ->has('documents.data')
            ->has('types')
            ->has('accessLevels'),
        );
});

it('renders the document create page', function () {
    $this->actingAs($this->admin)
        ->get(route('employee-documents.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employee-documents/create')
            ->has('employees')
            ->has('types')
            ->has('accessLevels'),
        );
});

it('renders the document edit page with the serialized document', function () {
    $document = EmployeeDocument::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->get(route('employee-documents.edit', $document))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employee-documents/edit')
            ->where('document.id', $document->id)
            ->has('types')
            ->has('accessLevels'),
        );
});

it('creates a document', function () {
    $this->actingAs($this->admin)
        ->post(route('employee-documents.store'), documentPayload())
        ->assertRedirect();

    $document = EmployeeDocument::where('number', '3174010101900001')->firstOrFail();

    expect($document->document_type)->toBe('ktp');
    expect($document->access_level)->toBe('internal');
});

it('rejects an expiry before the issue date', function () {
    $this->actingAs($this->admin)
        ->post(route('employee-documents.store'), documentPayload([
            'issued_at' => '2024-01-01',
            'expired_at' => '2023-01-01',
        ]))
        ->assertSessionHasErrors('expired_at');
});

it('flags an expired document', function () {
    EmployeeDocument::factory()->expired()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->get(route('employee-documents.index', ['status' => 'expired']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('documents.data.0.expiry_status', 'expired'),
        );
});

it('updates a document', function () {
    $document = EmployeeDocument::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->put(route('employee-documents.update', $document), documentPayload([
            'number' => 'UPDATED-123',
        ]))
        ->assertRedirect();

    expect($document->fresh()->number)->toBe('UPDATED-123');
});

it('deletes a document', function () {
    $document = EmployeeDocument::factory()->create(['employee_id' => $this->employee->id]);

    $this->actingAs($this->admin)
        ->delete(route('employee-documents.destroy', $document))
        ->assertRedirect();

    $this->assertDatabaseMissing('employee_documents', ['id' => $document->id]);
});

it('forbids users without employee.update from creating', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('employee.update'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('employee-documents.store'), documentPayload())
        ->assertForbidden();
});
