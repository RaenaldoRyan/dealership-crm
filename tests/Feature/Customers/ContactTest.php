<?php

use App\Core\Models\User;
use App\Customers\Models\Contact;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('contacts.index'))
        ->assertRedirect(route('login'));
});

test('a rep can create a contact they own', function () {
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $someoneElse = User::factory()->rep()->create();

    $this->actingAs($rep)
        ->post(route('contacts.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'user_id' => $someoneElse->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contacts.index'));

    $this->assertDatabaseHas('contacts', [
        'user_id' => $rep->id,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => null,
    ]);

    expect(Contact::query()->where('user_id', $someoneElse->id)->exists())->toBeFalse();
});

test('a rep can update their own contact', function () {
    $rep = User::factory()->rep()->create();
    $someoneElse = User::factory()->manager()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => null,
    ]);

    $this->actingAs($rep)
        ->put(route('contacts.update', $contact), [
            'name' => 'Ada King',
            'email' => 'ada.king@example.com',
            'phone' => '555-0199',
            'user_id' => $someoneElse->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contacts.index'));

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'user_id' => $rep->id,
        'name' => 'Ada King',
        'email' => 'ada.king@example.com',
        'phone' => '555-0199',
    ]);
});

test('a manager can update another reps contact', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();

    $this->actingAs($manager)
        ->put(route('contacts.update', $contact), [
            'name' => 'Updated By Manager',
            'email' => 'managed@example.com',
            'phone' => '555-0111',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contacts.index'));

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'user_id' => $rep->id,
        'name' => 'Updated By Manager',
        'email' => 'managed@example.com',
    ]);
});

test('a manager can view another reps contact', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $contact = Contact::factory()->for($rep, 'owner')->create([
        'name' => 'Private Contact',
        'email' => 'private@example.com',
    ]);

    $this->actingAs($manager)
        ->get(route('contacts.edit', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/Edit')
            ->where('contact.name', 'Private Contact')
            ->where('contact.email', 'private@example.com')
            ->where('contact.owner.name', 'Riley Rep')
        );
});

test('a rep gets 403 on another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create([
        'name' => 'Private Contact',
        'email' => 'private@example.com',
    ]);

    $this->actingAs($rep)
        ->get(route('contacts.edit', $contact))
        ->assertForbidden();

    $this->actingAs($rep)
        ->put(route('contacts.update', $contact), [
            'name' => 'Stolen',
            'email' => 'stolen@example.com',
            'phone' => '555-0000',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'name' => 'Private Contact',
        'email' => 'private@example.com',
    ]);
});

test('the contact index only shows a reps own contacts', function () {
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $other = User::factory()->rep()->create(['name' => 'Other Rep']);

    Contact::factory()->for($rep, 'owner')->create([
        'name' => 'Own Contact',
        'email' => 'own@example.com',
    ]);

    Contact::factory()->for($other, 'owner')->create([
        'name' => 'Hidden Contact',
        'email' => 'hidden@example.com',
    ]);

    $this->actingAs($rep)
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/Index')
            ->has('contacts', 1)
            ->where('contacts.0.name', 'Own Contact')
            ->where('contacts.0.email', 'own@example.com')
            ->where('contacts.0.owner.name', 'Riley Rep')
        );
});

test('a manager sees every contact', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $other = User::factory()->rep()->create(['name' => 'Other Rep']);

    Contact::factory()->for($rep, 'owner')->create(['email' => 'own@example.com']);
    Contact::factory()->for($other, 'owner')->create(['email' => 'other@example.com']);

    $this->actingAs($manager)
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/Index')
            ->has('contacts', 2)
            ->where('contacts', function (mixed $contacts): bool {
                $emails = collect($contacts)->pluck('email')->sort()->values()->all();
                $owners = collect($contacts)->pluck('owner.name')->sort()->values()->all();

                return $emails === ['other@example.com', 'own@example.com']
                    && $owners === ['Other Rep', 'Riley Rep'];
            })
        );
});

test('a contact requires a name and email', function () {
    $rep = User::factory()->rep()->create();

    $this->actingAs($rep)
        ->from(route('contacts.create'))
        ->post(route('contacts.store'), [])
        ->assertSessionHasErrors([
            'name' => 'The name field is required.',
            'email' => 'The email field is required.',
        ])
        ->assertRedirect(route('contacts.create'));

    $this->assertDatabaseCount('contacts', 0);
});

test('a contact email must be valid', function () {
    $rep = User::factory()->rep()->create();

    $this->actingAs($rep)
        ->from(route('contacts.create'))
        ->post(route('contacts.store'), [
            'name' => 'Ada Lovelace',
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors([
            'email' => 'The email field must be a valid email address.',
        ])
        ->assertRedirect(route('contacts.create'));

    $this->assertDatabaseCount('contacts', 0);
});
