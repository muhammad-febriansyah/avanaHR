<?php

use App\Actions\Platform\CreatePlatformBackupAction;
use App\Jobs\RestorePlatformBackup;
use App\Models\Backup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->superadmin = User::factory()->create(['is_super_admin' => true, 'tenant_id' => null]);
    $this->tenantUser = User::factory()->create(['is_super_admin' => false, 'tenant_id' => null]);
});

it('creates a real backup file with a recorded size', function () {
    $this->actingAs($this->superadmin)
        ->post(route('platform.backups.store'))
        ->assertRedirect();

    $backup = Backup::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    expect($backup->status)->toBe('completed')
        ->and((int) $backup->size_bytes)->toBeGreaterThan(0)
        ->and(Storage::disk('local')->exists($backup->location))->toBeTrue();
});

it('downloads a completed backup', function () {
    $backup = app(CreatePlatformBackupAction::class)->execute();

    $this->actingAs($this->superadmin)
        ->get(route('platform.backups.download', $backup))
        ->assertOk();
});

it('dispatches a queued restore job', function () {
    Bus::fake();
    $backup = app(CreatePlatformBackupAction::class)->execute();

    $this->actingAs($this->superadmin)
        ->post(route('platform.backups.restore', $backup))
        ->assertRedirect();

    Bus::assertDispatched(RestorePlatformBackup::class);
});

it('does not restore when the backup file is missing', function () {
    Bus::fake();
    $backup = Backup::create([
        'tenant_id' => null, 'type' => 'full', 'status' => 'completed',
        'location' => 'backups/missing.sql', 'size_bytes' => 10,
    ]);

    $this->actingAs($this->superadmin)
        ->post(route('platform.backups.restore', $backup))
        ->assertRedirect();

    Bus::assertNotDispatched(RestorePlatformBackup::class);
});

it('blocks backup actions for non super-admins', function () {
    $this->actingAs($this->tenantUser)
        ->post(route('platform.backups.store'))
        ->assertForbidden();
});
