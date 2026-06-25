<?php

use App\Models\CustomField;
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

function fieldPayload(array $overrides = []): array
{
    return array_merge([
        'entity_type' => 'employee',
        'key' => 'nomor_koperasi',
        'label' => 'Nomor Koperasi',
        'type' => 'text',
        'options' => [],
        'is_required' => true,
        'order' => 3,
    ], $overrides);
}

it('renders the custom field index', function () {
    $this->actingAs($this->admin)
        ->get(route('custom-fields.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('custom-fields/index')
            ->has('fields.data')
            ->has('entities')
            ->has('types'),
        );
});

it('creates a text custom field', function () {
    $this->actingAs($this->admin)
        ->post(route('custom-fields.store'), fieldPayload())
        ->assertRedirect();

    $field = CustomField::where('key', 'nomor_koperasi')->firstOrFail();

    expect($field->entity_type)->toBe('employee');
    expect($field->is_required)->toBeTrue();
    expect($field->options)->toBeNull();
});

it('stores options only for select type', function () {
    $this->actingAs($this->admin)
        ->post(route('custom-fields.store'), fieldPayload([
            'key' => 'shift_pref',
            'type' => 'select',
            'options' => ['Pagi', 'Sore', 'Malam'],
        ]))
        ->assertRedirect();

    $field = CustomField::where('key', 'shift_pref')->firstOrFail();

    expect($field->options)->toBe(['Pagi', 'Sore', 'Malam']);
});

it('rejects a duplicate key within the same entity', function () {
    CustomField::factory()->create(['key' => 'nomor_koperasi', 'entity_type' => 'employee']);

    $this->actingAs($this->admin)
        ->post(route('custom-fields.store'), fieldPayload())
        ->assertSessionHasErrors('key');
});

it('rejects an invalid key format', function () {
    $this->actingAs($this->admin)
        ->post(route('custom-fields.store'), fieldPayload(['key' => 'Bad Key!']))
        ->assertSessionHasErrors('key');
});

it('updates a custom field', function () {
    $field = CustomField::factory()->create(['entity_type' => 'employee']);

    $this->actingAs($this->admin)
        ->put(route('custom-fields.update', $field), [
            'label' => 'Label Baru',
            'type' => 'number',
            'options' => [],
            'is_required' => false,
            'order' => 5,
        ])
        ->assertRedirect();

    expect($field->fresh()->label)->toBe('Label Baru');
    expect($field->fresh()->type)->toBe('number');
});

it('deletes a custom field', function () {
    $field = CustomField::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('custom-fields.destroy', $field))
        ->assertRedirect();

    $this->assertDatabaseMissing('custom_fields', ['id' => $field->id]);
});

it('forbids users without setting.manage', function () {
    $employee = User::where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('setting.manage'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->get(route('custom-fields.index'))
        ->assertForbidden();
});
