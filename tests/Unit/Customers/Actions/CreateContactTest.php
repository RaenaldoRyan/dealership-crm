<?php

use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Customers\Actions\CreateContact;
use App\Customers\Models\Contact;

it('stores a contact owned by the acting user', function () {
    $rep = User::factory()->rep()->create();
    $someoneElse = User::factory()->rep()->create();

    $contact = app(CreateContact::class)->handle($rep, [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '555-0199',
        'user_id' => $someoneElse->id,
    ]);

    expect($contact->user_id)->toBe($rep->id)
        ->and($contact->name)->toBe('Ada Lovelace')
        ->and($contact->phone)->toBe('555-0199');

    $this->assertDatabaseMissing('contacts', [
        'user_id' => $someoneElse->id,
    ]);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Contact::class,
        'subject_id' => $contact->id,
        'user_id' => $rep->id,
        'type' => ActivityType::ContactCreated->value,
        'description' => 'Contact created',
    ]);
});

it('leaves the phone empty when none is given', function () {
    $rep = User::factory()->rep()->create();

    $contact = app(CreateContact::class)->handle($rep, [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    expect($contact->phone)->toBeNull();
});
