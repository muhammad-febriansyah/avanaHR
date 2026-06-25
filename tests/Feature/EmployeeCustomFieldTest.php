<?php

use App\Models\CustomField;
use App\Models\Employee;
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

it('exposes custom field definitions on the create page', function () {
    $this->actingAs($this->admin)
        ->get(route('employees.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/create')
            ->has('customFields')
            // seeder defines ukuran_seragam + golongan_darah
            ->where('customFields.0.key', fn ($key) => is_string($key)),
        );
});

it('persists custom field values when creating an employee', function () {
    $payload = validEmployeePayload([
        'employee_no' => 'EMP-CF-1',
        'custom_fields' => ['ukuran_seragam' => 'L', 'golongan_darah' => 'O'],
    ]);

    $this->actingAs($this->admin)
        ->post(route('employees.store'), $payload)
        ->assertRedirect(route('employees.index'));

    $employee = Employee::where('employee_no', 'EMP-CF-1')->firstOrFail();
    $values = $employee->customFieldValues()->with('customField')->get()
        ->mapWithKeys(fn ($v) => [$v->customField->key => $v->value]);

    expect($values['ukuran_seragam'])->toBe('L');
    expect($values['golongan_darah'])->toBe('O');
});

it('rejects a select custom field value outside its options', function () {
    $this->actingAs($this->admin)
        ->post(route('employees.store'), validEmployeePayload([
            'employee_no' => 'EMP-CF-2',
            'custom_fields' => ['ukuran_seragam' => 'XXXL'],
        ]))
        ->assertSessionHasErrors(['custom_fields.ukuran_seragam']);
});

it('enforces required custom fields', function () {
    CustomField::create([
        'tenant_id' => $this->tenant->id,
        'entity_type' => 'employee',
        'key' => 'no_sim',
        'label' => 'No. SIM',
        'type' => 'text',
        'is_required' => true,
        'order' => 5,
    ]);

    $this->actingAs($this->admin)
        ->post(route('employees.store'), validEmployeePayload(['employee_no' => 'EMP-CF-3']))
        ->assertSessionHasErrors(['custom_fields.no_sim']);
});

it('exposes existing custom field values on the edit page', function () {
    $employee = Employee::query()->firstOrFail();
    $field = CustomField::where('key', 'ukuran_seragam')->firstOrFail();
    $employee->customFieldValues()->create([
        'custom_field_id' => $field->id,
        'value' => 'M',
    ]);

    $this->actingAs($this->admin)
        ->get(route('employees.edit', $employee))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('employees/edit')
            ->has('customFields')
            ->where('customFieldValues.ukuran_seragam', 'M'),
        );
});

it('updates a custom field value when editing an employee', function () {
    $employee = Employee::query()->firstOrFail();
    $field = CustomField::where('key', 'ukuran_seragam')->firstOrFail();
    $employee->customFieldValues()->create([
        'custom_field_id' => $field->id,
        'value' => 'S',
    ]);

    $this->actingAs($this->admin)
        ->put(route('employees.update', $employee), validEmployeePayload([
            'employee_no' => $employee->employee_no,
            'first_name' => $employee->first_name,
            'status' => $employee->status->value,
            'custom_fields' => ['ukuran_seragam' => 'XL'],
        ]))
        ->assertRedirect(route('employees.index'));

    expect($employee->customFieldValues()->count())->toBe(1);
    expect($employee->customFieldValues()->first()->value)->toBe('XL');
});

it('clears a custom field value when submitted empty', function () {
    $employee = Employee::query()->firstOrFail();
    $field = CustomField::where('key', 'ukuran_seragam')->firstOrFail();
    $employee->customFieldValues()->create([
        'custom_field_id' => $field->id,
        'value' => 'M',
    ]);

    $this->actingAs($this->admin)
        ->put(route('employees.update', $employee), validEmployeePayload([
            'employee_no' => $employee->employee_no,
            'first_name' => $employee->first_name,
            'status' => $employee->status->value,
            'custom_fields' => ['ukuran_seragam' => ''],
        ]))
        ->assertRedirect(route('employees.index'));

    expect($employee->customFieldValues()->count())->toBe(0);
});
