<?php

use App\Core\Actions\CreateUser;
use App\Core\Enums\Role;
use App\Core\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

it('creates a verified staff account with the shared password', function () {
    $manager = User::factory()->manager()->create();

    $user = app(CreateUser::class)->handle($manager, [
        'name' => 'New Rep',
        'email' => 'new.rep@crm.test',
        'role' => Role::Rep->value,
        'password' => 'stolen-password',
        'email_verified_at' => null,
        'is_active' => '0',
    ]);

    expect($user->role)->toBe(Role::Rep)
        ->and($user->is_active)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and(Hash::check('stolen-password', $user->password))->toBeFalse();
});

it('leaves a new account active when no flag is posted', function () {
    $manager = User::factory()->manager()->create();

    $user = app(CreateUser::class)->handle($manager, [
        'name' => 'Active Rep',
        'email' => 'active.rep@crm.test',
        'role' => Role::Rep->value,
    ]);

    expect($user->is_active)->toBeTrue();
});

it('forbids a rep from creating a staff account', function () {
    $rep = User::factory()->rep()->create();

    app(CreateUser::class)->handle($rep, [
        'name' => 'Stolen',
        'email' => 'stolen@example.com',
        'role' => Role::Manager->value,
    ]);
})->throws(AuthorizationException::class);
