<?php

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('deals.index'))
        ->assertRedirect(route('login'));
});

test('a rep can create a deal they own', function () {
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $someoneElse = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create(['name' => 'Ada Lovelace']);
    $vehicle = Vehicle::factory()->for($contact)->create([
        'make' => 'Ford',
        'model' => 'Focus',
    ]);

    $this->actingAs($rep)
        ->post(route('deals.store'), [
            'contact_id' => $contact->id,
            'vehicle_id' => $vehicle->id,
            'value' => '15000.00',
            'stage' => DealStage::Enquiry->value,
            'user_id' => $someoneElse->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('deals.index'));

    $this->assertDatabaseHas('deals', [
        'user_id' => $rep->id,
        'contact_id' => $contact->id,
        'vehicle_id' => $vehicle->id,
        'value' => '15000.00',
        'stage' => 'enquiry',
    ]);

    expect(Deal::query()->where('user_id', $someoneElse->id)->exists())->toBeFalse();

    $this->actingAs($rep)
        ->get(route('deals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/Index')
            ->has('deals', 1)
            ->where('deals.0.contact.name', 'Ada Lovelace')
            ->where('deals.0.vehicle.name', 'Ford Focus')
            ->where('deals.0.owner.name', 'Riley Rep')
            ->where('deals.0.value', '15000.00')
            ->where('deals.0.stage', 'enquiry')
        );
});

test('the deal index only shows a reps own deals', function () {
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $other = User::factory()->rep()->create(['name' => 'Other Rep']);
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $otherContact = Contact::factory()->for($other, 'owner')->create();

    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '1000.00',
        'stage' => DealStage::Enquiry,
    ]);
    Deal::factory()->for($other, 'owner')->for($otherContact)->create([
        'value' => '9999.00',
        'stage' => DealStage::Sold,
    ]);

    $this->actingAs($rep)
        ->get(route('deals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/Index')
            ->has('deals', 1)
            ->where('deals.0.owner.name', 'Riley Rep')
            ->where('deals.0.value', '1000.00')
        );
});

test('a manager sees every deal', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $other = User::factory()->rep()->create(['name' => 'Other Rep']);

    Deal::factory()->for($rep, 'owner')->create(['value' => '1000.00']);
    Deal::factory()->for($other, 'owner')->create(['value' => '2000.00']);

    $this->actingAs($manager)
        ->get(route('deals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/Index')
            ->has('deals', 2)
            ->where('deals', function (mixed $deals): bool {
                $owners = collect($deals)->pluck('owner.name')->sort()->values()->all();

                return $owners === ['Other Rep', 'Riley Rep'];
            })
        );
});

test('a rep can change the stage of their own deal', function () {
    $rep = User::factory()->rep()->create();
    $someoneElse = User::factory()->manager()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create([
        'value' => '15000.00',
        'stage' => DealStage::Enquiry,
    ]);

    $this->actingAs($rep)
        ->patch(route('deals.update', $deal), [
            'stage' => DealStage::Sold->value,
            'value' => '1.00',
            'user_id' => $someoneElse->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('deals.index'));

    $this->assertDatabaseHas('deals', [
        'id' => $deal->id,
        'user_id' => $rep->id,
        'value' => '15000.00',
        'stage' => 'sold',
    ]);
});

test('a rep gets 403 on another reps deal', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $deal = Deal::factory()->for($other, 'owner')->create([
        'stage' => DealStage::Enquiry,
    ]);

    $this->actingAs($rep)
        ->patch(route('deals.update', $deal), [
            'stage' => DealStage::Lost->value,
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('deals', [
        'id' => $deal->id,
        'stage' => 'enquiry',
    ]);
});

test('a manager can change another reps deal stage', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create([
        'stage' => DealStage::Enquiry,
    ]);

    $this->actingAs($manager)
        ->patch(route('deals.update', $deal), [
            'stage' => DealStage::Negotiation->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('deals.index'));

    $this->assertDatabaseHas('deals', [
        'id' => $deal->id,
        'user_id' => $rep->id,
        'stage' => 'negotiation',
    ]);
});

test('the create form offers a manager every contact', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    Contact::factory()->for($rep, 'owner')->create(['name' => 'Riley Contact']);
    Contact::factory()->for($other, 'owner')->create(['name' => 'Other Contact']);

    $this->actingAs($manager)
        ->get(route('deals.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/Create')
            ->has('contacts', 2)
            ->where('contacts', function (mixed $contacts): bool {
                $names = collect($contacts)->pluck('name')->sort()->values()->all();

                return $names === ['Other Contact', 'Riley Contact'];
            })
        );
});

test('the create form only offers a reps own contacts', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    Contact::factory()->for($rep, 'owner')->create(['name' => 'Own Contact']);
    Contact::factory()->for($other, 'owner')->create(['name' => 'Hidden Contact']);

    $this->actingAs($rep)
        ->get(route('deals.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/Create')
            ->has('contacts', 1)
            ->where('contacts.0.name', 'Own Contact')
        );
});

test('a vehicle must belong to the selected contact', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $otherContact = Contact::factory()->for($rep, 'owner')->create();
    $vehicle = Vehicle::factory()->for($otherContact)->create();

    $this->actingAs($rep)
        ->from(route('deals.create'))
        ->post(route('deals.store'), [
            'contact_id' => $contact->id,
            'vehicle_id' => $vehicle->id,
            'value' => '15000.00',
            'stage' => DealStage::Enquiry->value,
        ])
        ->assertSessionHasErrors([
            'vehicle_id' => 'The vehicle must belong to the selected contact.',
        ])
        ->assertRedirect(route('deals.create'));

    $this->assertDatabaseCount('deals', 0);
});

test('a manager can open a deal for another reps contact', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create(['name' => 'Riley Contact']);

    $this->actingAs($manager)
        ->post(route('deals.store'), [
            'contact_id' => $contact->id,
            'value' => '15000.00',
            'stage' => DealStage::Enquiry->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('deals.index'));

    $this->assertDatabaseHas('deals', [
        'user_id' => $manager->id,
        'contact_id' => $contact->id,
        'value' => '15000.00',
        'stage' => 'enquiry',
    ]);

    $this->actingAs($manager)
        ->get(route('deals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/Index')
            ->has('deals', 1)
            ->where('deals.0.contact.name', 'Riley Contact')
            ->where('deals.0.owner.name', $manager->name)
        );
});

test('a rep gets 403 opening a deal for another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();

    $this->actingAs($rep)
        ->post(route('deals.store'), [
            'contact_id' => $contact->id,
            'value' => '15000.00',
            'stage' => DealStage::Enquiry->value,
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('deals', 0);
});

test('a deal requires a contact, value, and stage', function () {
    $rep = User::factory()->rep()->create();

    $this->actingAs($rep)
        ->from(route('deals.create'))
        ->post(route('deals.store'), [])
        ->assertSessionHasErrors([
            'contact_id' => 'The contact field is required.',
            'value' => 'The value field is required.',
            'stage' => 'The stage field is required.',
        ])
        ->assertRedirect(route('deals.create'));

    $this->assertDatabaseCount('deals', 0);
});
