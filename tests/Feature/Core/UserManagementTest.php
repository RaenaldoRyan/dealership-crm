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
            ->where('can.viewReports', true)
            ->where('users', function (mixed $users) use ($rep): bool {
                $created = collect($users)->firstWhere('email', 'new.rep@crm.test');

                return is_array($created)
                    && $created['name'] === 'New Rep'
                    && $created['role'] === 'rep'
                    && $created['roleLabel'] === 'Sales rep'
                    && $created['active'] === true
                    && $created['lastLogin'] === 'Never'
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
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.manageUsers', false)
            ->where('can.viewReports', false));

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
        ->delete('/users/'.$other->id)
        ->assertMethodNotAllowed();

    $this->assertDatabaseHas('users', [
        'id' => $other->id,
        'email' => $other->email,
        'role' => 'rep',
    ]);

    expect(User::query()->where('email', 'stolen@example.com')->exists())->toBeFalse();
});

test('a manager cannot delete a user', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();

    expect($manager->can('delete', $rep))->toBeFalse()
        ->and($manager->can('delete', $manager))->toBeFalse();

    $this->actingAs($manager)
        ->delete('/users/'.$rep->id)
        ->assertMethodNotAllowed();

    $this->assertDatabaseHas('users', [
        'id' => $manager->id,
    ]);

    $this->assertDatabaseHas('users', [
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

test('an active flag must be true or false', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->from(route('users.index', ['user' => 'new']))
        ->post(route('users.store'), [
            'name' => 'New Rep',
            'email' => 'new.rep@crm.test',
            'role' => Role::Rep->value,
            'is_active' => 'maybe',
        ])
        ->assertSessionHasErrors([
            'is_active' => 'The is active field must be true or false.',
        ])
        ->assertRedirect(route('users.index', ['user' => 'new']));
});

test('adding and editing open on the staff list', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);

    $this->actingAs($manager)
        ->get(route('users.create'))
        ->assertRedirect(route('users.index', ['user' => 'new']));

    $this->get(route('users.index', ['user' => 'new']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('panel.mode', 'create')
            ->where('panel.active', true)
            ->where('roles.0.label', 'Sales rep')
        );

    $this->get(route('users.edit', $rep))
        ->assertRedirect(route('users.index', ['user' => $rep->id]));

    $this->get(route('users.index', ['user' => $rep->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('panel.mode', 'edit')
            ->where('panel.name', 'Riley Rep')
            ->where('panel.role', 'rep')
            ->where('panel.active', true)
        );
});

test('the staff list shows status and a relative last login', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $manager = User::factory()->manager()->create(['name' => 'Morgan Hale']);
    $rep = User::factory()->rep()->inactive()->create([
        'name' => 'Riley Rep',
        'last_login_at' => now()->subHours(2),
    ]);

    $this->actingAs($manager)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('panel', null)
            ->where('users', function (mixed $users) use ($rep, $manager): bool {
                $rows = collect($users)->keyBy('id');
                $inactive = $rows->get($rep->id);
                $self = $rows->get($manager->id);

                return is_array($inactive)
                    && $inactive['roleLabel'] === 'Sales rep'
                    && $inactive['active'] === false
                    && $inactive['lastLogin'] === '2 hours ago'
                    && is_array($self)
                    && $self['roleLabel'] === 'Manager'
                    && $self['active'] === true
                    && $self['lastLogin'] === 'Never';
            })
        );
});

test('a manager can deactivate another account', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();

    $this->actingAs($manager)
        ->put(route('users.update', $rep), [
            'name' => $rep->name,
            'email' => $rep->email,
            'role' => Role::Rep->value,
            'is_active' => '0',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'));

    expect($rep->refresh()->is_active)->toBeFalse();
});

test('a manager cannot deactivate themselves', function () {
    $manager = User::factory()->manager()->create(['name' => 'Morgan Hale']);

    $this->actingAs($manager)
        ->from(route('users.index', ['user' => $manager->id]))
        ->put(route('users.update', $manager), [
            'name' => 'Someone Else',
            'email' => $manager->email,
            'role' => Role::Manager->value,
            'is_active' => '0',
        ])
        ->assertSessionHasErrors([
            'is_active' => 'You cannot deactivate your own account.',
        ])
        ->assertRedirect(route('users.index', ['user' => $manager->id]));

    expect($manager->refresh()->name)->toBe('Morgan Hale')
        ->and($manager->is_active)->toBeTrue();
});
