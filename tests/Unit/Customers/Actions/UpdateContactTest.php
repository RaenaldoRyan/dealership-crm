<?php

use App\Core\Models\User;
use App\Customers\Actions\UpdateContact;
use App\Customers\Models\Contact;
use Illuminate\Auth\Access\AuthorizationException;

it('updates the contact and keeps its owner', function () {
    $rep = User::factory()->rep()->create();
    $manager = User::factory()->manager()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => null,
    ]);

    $updated = app(UpdateContact::class)->handle($rep, $contact, [
        'name' => 'Ada King',
        'email' => 'ada.king@example.com',
        'phone' => '555-0199',
        'user_id' => $manager->id,
    ]);

    expect($updated->user_id)->toBe($rep->id)
        ->and($updated->name)->toBe('Ada King')
        ->and($updated->email)->toBe('ada.king@example.com')
        ->and($updated->phone)->toBe('555-0199');
});

it('forbids a rep from updating another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create([
        'name' => 'Private Contact',
    ]);

    expect(fn () => app(UpdateContact::class)->handle($rep, $contact, [
        'name' => 'Stolen',
        'email' => 'stolen@example.com',
    ]))->toThrow(AuthorizationException::class);

    expect($contact->refresh()->name)->toBe('Private Contact');
});
