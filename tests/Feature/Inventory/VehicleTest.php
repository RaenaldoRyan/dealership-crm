<?php

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $contact = Contact::factory()->create();

    $this->post(route('contacts.vehicles.store', $contact), [
        'make' => 'Ford',
        'model' => 'Focus',
        'year' => 2020,
        'registration' => 'AB12 CDE',
        'price' => '12500.50',
    ])->assertRedirect(route('login'));
});

test('a rep can add a vehicle to their own contact', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $someoneElse = Contact::factory()->for($rep, 'owner')->create();

    $this->actingAs($rep)
        ->post(route('contacts.vehicles.store', $contact), [
            'make' => 'Ford',
            'model' => 'Focus',
            'year' => 2020,
            'registration' => 'AB12 CDE',
            'price' => '12500.50',
            'contact_id' => $someoneElse->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contacts.edit', $contact));

    $this->assertDatabaseHas('vehicles', [
        'contact_id' => $contact->id,
        'make' => 'Ford',
        'model' => 'Focus',
        'year' => 2020,
        'registration' => 'AB12 CDE',
    ]);

    expect(Vehicle::query()->where('contact_id', $someoneElse->id)->exists())->toBeFalse();

    $this->actingAs($rep)
        ->get(route('contacts.edit', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/Edit')
            ->has('vehicles', 1)
            ->where('vehicles.0.make', 'Ford')
            ->where('vehicles.0.model', 'Focus')
            ->where('vehicles.0.year', 2020)
            ->where('vehicles.0.registration', 'AB12 CDE')
            ->where('vehicles.0.price', '12500.50')
        );
});

test('a contact page lists only that contacts vehicles', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $otherContact = Contact::factory()->for($rep, 'owner')->create();

    Vehicle::factory()->for($contact)->create(['registration' => 'OWN1']);
    Vehicle::factory()->for($otherContact)->create(['registration' => 'OTHER1']);

    $this->actingAs($rep)
        ->get(route('contacts.edit', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/Edit')
            ->has('vehicles', 1)
            ->where('vehicles.0.registration', 'OWN1')
        );
});

test('a manager can view another reps vehicle', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $otherContact = Contact::factory()->for($other, 'owner')->create();

    Vehicle::factory()->for($contact)->create(['registration' => 'OWN1']);
    Vehicle::factory()->for($otherContact)->create(['registration' => 'OTHER1']);

    $this->actingAs($manager)
        ->get(route('contacts.edit', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/Edit')
            ->has('vehicles', 1)
            ->where('vehicles.0.registration', 'OWN1')
        );
});

test('a rep gets 403 on another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();

    $this->actingAs($rep)
        ->post(route('contacts.vehicles.store', $contact), [
            'make' => 'Ford',
            'model' => 'Focus',
            'year' => 2020,
            'registration' => 'AB12 CDE',
            'price' => '12500.50',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('vehicles', 0);
});

test('a manager can add a vehicle to another reps contact', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();

    $this->actingAs($manager)
        ->post(route('contacts.vehicles.store', $contact), [
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2021,
            'registration' => 'CD34 EFG',
            'price' => '9999',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('contacts.edit', $contact));

    $this->assertDatabaseHas('vehicles', [
        'contact_id' => $contact->id,
        'make' => 'Toyota',
        'registration' => 'CD34 EFG',
    ]);
});

test('a vehicle requires its details', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();

    $this->actingAs($rep)
        ->from(route('contacts.edit', $contact))
        ->post(route('contacts.vehicles.store', $contact), [])
        ->assertSessionHasErrors([
            'make' => 'The make field is required.',
            'model' => 'The model field is required.',
            'year' => 'The year field is required.',
            'registration' => 'The registration field is required.',
            'price' => 'The price field is required.',
        ])
        ->assertRedirect(route('contacts.edit', $contact));

    $this->assertDatabaseCount('vehicles', 0);
});
