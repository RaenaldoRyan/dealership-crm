<?php

use App\Core\Actions\SetUserActive;
use App\Core\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

it('deactivates another staff account', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();

    app(SetUserActive::class)->handle($manager, $rep, false);

    expect($rep->refresh()->is_active)->toBeFalse();
});

it('refuses to deactivate the signed-in manager', function () {
    $manager = User::factory()->manager()->create();

    try {
        app(SetUserActive::class)->handle($manager, $manager, false);
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe([
            'is_active' => ['You cannot deactivate your own account.'],
        ])
            ->and($manager->refresh()->is_active)->toBeTrue();

        return;
    }

    throw new RuntimeException('The manager was deactivated.');
});

it('lets a manager stay active', function () {
    $manager = User::factory()->manager()->create();

    app(SetUserActive::class)->handle($manager, $manager, true);

    expect($manager->refresh()->is_active)->toBeTrue();
});

it('forbids a rep from changing an active flag', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();

    app(SetUserActive::class)->handle($rep, $other, false);
})->throws(AuthorizationException::class);
