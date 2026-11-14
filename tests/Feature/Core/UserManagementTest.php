<?php

use App\Core\Enums\Role;
use App\Core\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('users.index'))
        ->assertRedirect(route('login'));
});

test('a manager can create a rep', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->post(route('users.store'), [
            'name' => 'New Rep',
            'email' => 'new.rep@crm.test',
            'role' => Role::Rep->value,
            'password' => 'stolen-password',
            'email_verified_at' => null,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    $rep = User::query()->where('email', 'new.rep@crm.test')->first();

    expect($rep)->not->toBeNull()
        ->and($rep?->role)->toBe(Role::Rep)
        ->and($rep?->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', (string) $rep?->password))->toBeTrue();

    $this->actingAs($manager)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/Index')
            ->where('can.manageUsers', true)
            ->where('users', function (mixed $users) use ($rep): bool {
                $created = collect($users)->firstWhere('email', 'new.rep@crm.test');

                return is_array($created)
                    && $created['name'] === 'New Rep'
                    && $created['role'] === 'rep'
                    && $created['id'] === $rep?->id;
            })
        );
});

test('a rep gets 403 on every users route', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();

    $this->actingAs($rep)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('can.manageUsers', false));

    $this->actingAs($rep)
        ->get(route('users.index'))
        ->assertForbidden();

    $this->actingAs($rep)
        ->get(route('users.create'))
        ->assertForbidden();

    $this->actingAs($rep)
        ->post(route('users.store'), [
            'name' => 'Stolen',
            'email' => 'stolen@example.com',
            'role' => Role::Manager->value,
        ])
        ->assertForbidden();

    $this->actingAs($rep)
        ->get(route('users.edit', $other))
        ->assertForbidden();

    $this->actingAs($rep)
        ->put(route('users.update', $other), [
            'name' => 'Stolen',
            'email' => 'stolen@example.com',
            'role' => Role::Manager->value,
        ])
        ->assertForbidden();

    $this->actingAs($rep)
        ->delete(route('users.destroy', $other))
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $other->id,
        'email' => $other->email,
        'role' => 'rep',
    ]);

    expect(User::query()->where('email', 'stolen@example.com')->exists())->toBeFalse();
});

test('a manager cannot delete themselves', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();

    $this->actingAs($manager)
        ->delete(route('users.destroy', $manager))
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $manager->id,
    ]);

    $this->actingAs($manager)
        ->delete(route('users.destroy', $rep))
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $rep->id,
    ]);
});

test('a user requires a name, email, and role', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->from(route('users.create'))
        ->post(route('users.store'), [])
        ->assertSessionHasErrors([
            'name' => 'The name field is required.',
            'email' => 'The email field is required.',
            'role' => 'The role field is required.',
        ])
        ->assertRedirect(route('users.create'));
});
