<?php

use App\Models\Backup;
use App\Models\User;
use Database\Seeders\DemoTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create([
        'tenant_id' => null,
        'is_super_admin' => true,
    ]);
});

it('lists backups for the super-admin', function () {
    Backup::factory()->count(3)->create();

    $this->actingAs($this->superAdmin)
        ->get(route('platform.backups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/backups/index')
            ->has('backups.data', 3),
        );
});

it('creates a backup record', function () {
    $this->actingAs($this->superAdmin)
        ->post(route('platform.backups.store'))
        ->assertRedirect();

    $backup = Backup::firstOrFail();

    expect($backup->type)->toBe('full');
    expect($backup->status)->toBe('completed');
    expect($backup->location)->toContain('backups/');
});

it('acknowledges a restore for a completed backup', function () {
    $backup = Backup::factory()->create();

    $this->actingAs($this->superAdmin)
        ->post(route('platform.backups.restore', $backup))
        ->assertRedirect();
});

it('blocks restoring a failed backup', function () {
    $backup = Backup::factory()->failed()->create();

    $this->actingAs($this->superAdmin)
        ->from(route('platform.backups.index'))
        ->post(route('platform.backups.restore', $backup))
        ->assertRedirect(route('platform.backups.index'));
});

it('forbids non super-admins from backups', function () {
    $this->seed(DemoTenantSeeder::class);
    $admin = User::where('email', 'admin@avanahr.id')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('platform.backups.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('platform.backups.store'))
        ->assertForbidden();
});
