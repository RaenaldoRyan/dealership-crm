<?php

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
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
        'registration' => 'AB12CDE',
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
            ->where('vehicles.0.registration', 'OWN 1')
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
            ->where('vehicles.0.registration', 'OWN 1')
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
        'registration' => 'CD34EFG',
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

test('guests are redirected from the inventory', function () {
    $this->get(route('vehicles.index'))->assertRedirect(route('login'));
});

test('a rep sees stock and another reps vehicles', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $customer = Contact::factory()->for($other, 'owner')->create(['name' => 'Other Customer']);

    Vehicle::factory()->for($customer)->create([
        'registration' => 'AB12 CDE',
        'make' => 'Ford',
    ]);
    Vehicle::factory()->stock()->create([
        'registration' => 'CD34 EFG',
        'make' => 'Audi',
    ]);

    $this->actingAs($rep)
        ->get(route('vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/Index')
            ->has('vehicles.data', 2)
            ->where('vehicles.data.0.registration', 'CD34 EFG')
            ->where('vehicles.data.0.customer', null)
            ->where('vehicles.data.1.registration', 'AB12 CDE')
            ->where('vehicles.data.1.customer', 'Other Customer')
            ->where('vehicles.from', 1)
            ->where('vehicles.to', 2)
            ->where('vehicles.total', 2)
        );
});

test('inventory search matches a plate or a customer name', function () {
    $rep = User::factory()->rep()->create();
    $sarah = Contact::factory()->for($rep, 'owner')->create(['name' => 'Sarah Mitchell']);

    Vehicle::factory()->for($sarah)->create([
        'registration' => 'AB22 CDE',
        'make' => 'Ford',
    ]);
    Vehicle::factory()->stock()->create([
        'registration' => 'CD34 EFG',
        'make' => 'Toyota',
    ]);

    $this->actingAs($rep)
        ->get(route('vehicles.index', ['search' => 'ab22 cde']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.registration', 'AB22 CDE')
        );

    $this->actingAs($rep)
        ->get(route('vehicles.index', ['search' => 'Sarah']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.customer', 'Sarah Mitchell')
        );
});

test('the inventory list can be filtered by status', function () {
    $rep = User::factory()->rep()->create();

    Vehicle::factory()->create([
        'registration' => 'AB12 CDE',
        'status' => VehicleStatus::InStock,
    ]);
    Vehicle::factory()->create([
        'registration' => 'CD34 EFG',
        'status' => VehicleStatus::Sold,
    ]);

    $this->actingAs($rep)
        ->get(route('vehicles.index', ['status' => 'sold']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.data.0.registration', 'CD34 EFG')
            ->where('vehicles.data.0.status', 'sold')
            ->where('vehicles.data.0.statusLabel', 'Sold')
        );
});

test('the inventory list shows the latest deal stage', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $vehicle = Vehicle::factory()->for($contact)->create(['registration' => 'AB22 CDE']);

    Deal::factory()->for($contact)->create([
        'vehicle_id' => $vehicle->id,
        'user_id' => $rep->id,
        'stage' => DealStage::Enquiry,
    ]);
    Deal::factory()->for($contact)->create([
        'vehicle_id' => $vehicle->id,
        'user_id' => $rep->id,
        'stage' => DealStage::Negotiation,
    ]);

    $this->actingAs($rep)
        ->get(route('vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('vehicles.data.0.deal.label', 'Negotiation')
        );
});

test('the inventory list pages fifteen vehicles at a time', function () {
    $rep = User::factory()->rep()->create();

    Vehicle::factory()->count(16)->create();

    $this->actingAs($rep)
        ->get(route('vehicles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 15)
            ->where('vehicles.total', 16)
            ->where('vehicles.from', 1)
            ->where('vehicles.to', 15)
        );

    $this->actingAs($rep)
        ->get(route('vehicles.index', ['page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('vehicles.data', 1)
            ->where('vehicles.from', 16)
            ->where('vehicles.to', 16)
        );
});

test('a rep can record a stock vehicle', function () {
    $rep = User::factory()->rep()->create();

    $this->actingAs($rep)
        ->post(route('vehicles.store'), [
            'make' => 'Ford',
            'model' => 'Puma',
            'year' => 2022,
            'colour' => 'Agate Black',
            'fuel_type' => 'petrol',
            'transmission' => 'manual',
            'mileage' => '18,400',
            'registration' => 'AB22 CDE',
            'price' => '£18,950',
        ])
        ->assertSessionHasNoErrors();

    $vehicle = Vehicle::query()->first();

    expect($vehicle)->not->toBeNull();

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'registration' => 'AB22CDE',
        'contact_id' => null,
        'mileage' => 18400,
        'status' => 'in_stock',
        'fuel_type' => 'petrol',
        'price' => '18950.00',
    ]);

    $this->actingAs($rep)
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/Show')
            ->where('vehicle.registration', 'AB22 CDE')
            ->where('vehicle.customer', null)
            ->where('canUpdate', true)
        );
});

test('a rep can save a vehicle against their own contact', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();

    $this->actingAs($rep)
        ->post(route('vehicles.store'), [
            'make' => 'Ford',
            'model' => 'Puma',
            'year' => 2022,
            'registration' => 'AB22 CDE',
            'price' => '18950',
            'contact_id' => $contact->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('vehicles', [
        'contact_id' => $contact->id,
        'registration' => 'AB22CDE',
    ]);

    $vehicle = Vehicle::query()->where('registration', 'AB22CDE')->firstOrFail();

    $this->actingAs($rep)
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canUpdate', true)
            ->where('vehicle.customer', $contact->name)
        );
});

test('a rep gets 403 when assigning another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();

    $this->actingAs($rep)
        ->post(route('vehicles.store'), [
            'make' => 'Ford',
            'model' => 'Puma',
            'year' => 2022,
            'registration' => 'AB22 CDE',
            'price' => '18950',
            'contact_id' => $contact->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('vehicles', 0);
});

test('a vehicle registration must be a uk plate and unique', function () {
    $rep = User::factory()->rep()->create();
    Vehicle::factory()->create(['registration' => 'AB22 CDE']);

    $this->actingAs($rep)
        ->from(route('vehicles.create'))
        ->post(route('vehicles.store'), [
            'make' => 'Ford',
            'model' => 'Puma',
            'year' => 2022,
            'registration' => 'NOT A PLATE',
            'price' => '18950',
        ])
        ->assertSessionHasErrors('registration')
        ->assertRedirect(route('vehicles.create'));

    $this->actingAs($rep)
        ->from(route('vehicles.create'))
        ->post(route('vehicles.store'), [
            'make' => 'Ford',
            'model' => 'Puma',
            'year' => 2022,
            'registration' => 'ab22 cde',
            'price' => '18950',
        ])
        ->assertSessionHasErrors('registration');

    $this->assertDatabaseCount('vehicles', 1);
});

test('the add vehicle form only offers contacts the user can update', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $mine = Contact::factory()->for($rep, 'owner')->create(['name' => 'Mine']);
    Contact::factory()->for($other, 'owner')->create(['name' => 'Theirs']);

    $this->actingAs($rep)
        ->get(route('vehicles.create', ['contact' => $mine->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inventory/Create')
            ->where('contactId', $mine->id)
            ->has('contacts', 1)
            ->where('contacts.0.name', 'Mine')
        );

    $theirs = Contact::query()->where('name', 'Theirs')->firstOrFail();

    $this->actingAs($rep)
        ->get(route('vehicles.create', ['contact' => $theirs->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contactId', null)
            ->has('contacts', 1)
        );
});

test('a rep can update a stock vehicle without changing its status', function () {
    $rep = User::factory()->rep()->create();
    $vehicle = Vehicle::factory()->stock()->create([
        'registration' => 'AB22 CDE',
        'status' => VehicleStatus::Reserved,
        'model' => 'Focus',
    ]);

    $this->actingAs($rep)
        ->put(route('vehicles.update', $vehicle), [
            'make' => 'Ford',
            'model' => 'Puma',
            'year' => 2022,
            'registration' => 'AB22 CDE',
            'price' => '18000',
            'contact_id' => '',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('vehicles.show', $vehicle));

    $vehicle->refresh();

    expect($vehicle->model)->toBe('Puma')
        ->and($vehicle->status)->toBe(VehicleStatus::Reserved)
        ->and($vehicle->contact_id)->toBeNull();
});

test('a rep gets 403 when editing another reps customer vehicle', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();
    $vehicle = Vehicle::factory()->for($contact)->create();

    $this->actingAs($rep)
        ->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canUpdate', false)
        );

    $this->actingAs($rep)
        ->get(route('vehicles.edit', $vehicle))
        ->assertForbidden();

    $this->actingAs($rep)
        ->put(route('vehicles.update', $vehicle), [
            'make' => 'Ford',
            'model' => 'Puma',
            'year' => 2022,
            'registration' => 'AB22 CDE',
            'price' => '1',
        ])
        ->assertForbidden();
});

test('a manager can update another reps vehicle', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $vehicle = Vehicle::factory()->for($contact)->create([
        'registration' => 'CD34 EFG',
        'model' => 'Focus',
    ]);

    $this->actingAs($manager)
        ->put(route('vehicles.update', $vehicle), [
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2021,
            'registration' => 'CD34 EFG',
            'price' => '9999',
            'contact_id' => $contact->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('vehicles.show', $vehicle));

    expect($vehicle->refresh()->model)->toBe('Corolla');
});

test('vehicles cannot be deleted', function () {
    $manager = User::factory()->manager()->create();
    $vehicle = Vehicle::factory()->create();

    expect($manager->can('delete', $vehicle))->toBeFalse();

    $this->actingAs($manager)
        ->delete(route('vehicles.show', $vehicle))
        ->assertMethodNotAllowed();

    $this->assertModelExists($vehicle);
});
