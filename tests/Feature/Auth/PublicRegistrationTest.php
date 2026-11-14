<?php

use App\Core\Models\User;

test('guests cannot register', function () {
    $this->get('/register')->assertNotFound();

    $this->post('/register', [
        'name' => 'Stranger',
        'email' => 'stranger@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();

    expect(User::query()->where('email', 'stranger@example.com')->exists())->toBeFalse();
});
