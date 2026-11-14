<?php

use App\Core\Enums\Role;
use App\Core\Models\User;

test('users are reps unless a role is chosen', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(Role::Rep);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'rep',
    ]);
});

test('the rep factory state stores a rep', function () {
    $user = User::factory()->rep()->create();

    expect($user->role)->toBe(Role::Rep);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'rep',
    ]);
});

test('the manager factory state stores a manager', function () {
    $user = User::factory()->manager()->create();

    expect($user->role)->toBe(Role::Manager);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'manager',
    ]);
});

test('users created without a role are reps', function () {
    $user = User::create([
        'name' => 'Alex Rep',
        'email' => 'alex@example.com',
        'password' => 'password',
    ]);

    expect($user->fresh()->role)->toBe(Role::Rep);
});
