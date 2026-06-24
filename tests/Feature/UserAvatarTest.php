<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('falls back to a generated avatar when none is uploaded', function () {
    $user = User::factory()->create(['name' => 'Budi Santoso', 'avatar_path' => null]);

    expect($user->avatar_url)->toContain('ui-avatars.com')
        ->and($user->avatar_url)->toContain('Budi+Santoso');
});

it('uses the stored avatar path when present', function () {
    $user = User::factory()->create(['avatar_path' => 'avatars/budi.png']);

    expect($user->avatar_url)->toContain('avatars/budi.png');
});

it('always exposes avatar_url when serialized for the frontend', function () {
    $user = User::factory()->create();

    expect($user->toArray())->toHaveKey('avatar_url');
});
