<?php

use App\Core\Actions\UpdateUser;
use App\Core\Enums\Role;
use App\Core\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

it('updates the account and leaves the password alone', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create([
        'name' => 'Old Name',
        'email' => 'old@crm.test',
    ]);

    app(UpdateUser::class)->handle($manager, $rep, [
        'name' => 'New Name',
        'email' => 'new@crm.test',
        'role' => Role::Manager->value,
        'is_active' => '0',
        'password' => 'stolen-password',
    ]);

    $rep->refresh();

    expect($rep->name)->toBe('New Name')
        ->and($rep->email)->toBe('new@crm.test')
        ->and($rep->role)->toBe(Role::Manager)
        ->and($rep->is_active)->toBeFalse()
        ->and(Hash::check('password', $rep->password))->toBeTrue();
});

it('rolls back the other changes when a manager deactivates themselves', function () {
    $manager = User::factory()->manager()->create(['name' => 'Morgan Hale']);

    try {
        app(UpdateUser::class)->handle($manager, $manager, [
            'name' => 'Someone Else',
            'email' => $manager->email,
            'role' => Role::Manager->value,
            'is_active' => false,
        ]);
    } catch (ValidationException $exception) {
        expect($exception->errors()['is_active'])->toBe(['You cannot deactivate your own account.'])
            ->and($manager->refresh()->name)->toBe('Morgan Hale')
            ->and($manager->is_active)->toBeTrue();

        return;
    }

    throw new RuntimeException('The manager was updated.');
});

it('forbids a rep from updating a staff account', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();

    app(UpdateUser::class)->handle($rep, $other, [
        'name' => 'Stolen',
        'email' => 'stolen@example.com',
        'role' => Role::Manager->value,
    ]);
})->throws(AuthorizationException::class);
