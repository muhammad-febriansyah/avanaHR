<?php

use App\Models\BankFile;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
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
    $period = PayrollPeriod::factory()->create();
    $this->run = PayrollRun::factory()->create(['period_id' => $period->id, 'net_total' => 123_000_000]);
});

it('renders the bank files index', function () {
    $this->actingAs($this->admin)
        ->get(route('bank-files.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bank-files/index')
            ->has('bankFiles.data')
            ->has('banks')
            ->has('formats')
            ->has('options.runs'),
        );
});

it('creates a bank file deriving total from the run and a file path', function () {
    $this->actingAs($this->admin)
        ->post(route('bank-files.store'), [
            'run_id' => $this->run->id,
            'bank_code' => 'BCA',
            'format' => 'csv',
        ])
        ->assertRedirect();

    $file = BankFile::firstOrFail();

    expect((int) $file->total)->toBe(123_000_000);
    expect($file->file_path)->toContain('bank-files/');
    expect($file->file_path)->toEndWith('.csv');
});

it('rejects an invalid bank code', function () {
    $this->actingAs($this->admin)
        ->post(route('bank-files.store'), [
            'run_id' => $this->run->id,
            'bank_code' => 'Jago',
            'format' => 'csv',
        ])
        ->assertSessionHasErrors('bank_code');
});

it('rejects an invalid format', function () {
    $this->actingAs($this->admin)
        ->post(route('bank-files.store'), [
            'run_id' => $this->run->id,
            'bank_code' => 'BCA',
            'format' => 'pdf',
        ])
        ->assertSessionHasErrors('format');
});

it('deletes a bank file', function () {
    $file = BankFile::factory()->create(['run_id' => $this->run->id]);

    $this->actingAs($this->admin)
        ->delete(route('bank-files.destroy', $file))
        ->assertRedirect();

    $this->assertDatabaseMissing('bank_files', ['id' => $file->id]);
});

it('forbids users without payroll.approve from creating bank files', function () {
    $employee = User::where('email', '!=', 'admin@avanahr.id')
        ->where('tenant_id', $this->tenant->id)
        ->get()
        ->first(fn (User $user) => ! $user->can('payroll.approve'));

    expect($employee)->not->toBeNull();

    $this->actingAs($employee)
        ->post(route('bank-files.store'), [
            'run_id' => $this->run->id,
            'bank_code' => 'BCA',
            'format' => 'csv',
        ])
        ->assertForbidden();
});
