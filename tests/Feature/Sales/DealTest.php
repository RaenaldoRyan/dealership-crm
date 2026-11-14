<?php

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Enums\VehicleStatus;
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
            ->where('metrics.pipelineValue', '1000.00')
            ->where('metrics.openDeals', 1)
            ->where('metrics.conversion', 0)
            ->where('metrics.averageDaysToClose', 0)
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
            ->where('metrics.pipelineValue', '3000.00')
            ->where('metrics.openDeals', 2)
            ->where('metrics.conversion', 0)
            ->where('metrics.averageDaysToClose', 0)
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
        ->from(route('deals.index'))
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
        ->from(route('deals.index'))
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

test('a new deal can start with the contact already chosen', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $hidden = Contact::factory()->for($other, 'owner')->create();

    $this->actingAs($rep)
        ->get(route('deals.create', ['contact' => $contact->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contactId', $contact->id)
        );

    $this->actingAs($rep)
        ->get(route('deals.create', ['contact' => $hidden->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contactId', null)
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

test('a rep can open their own deal', function () {
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $contact = Contact::factory()->for($rep, 'owner')->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '555-0100',
    ]);
    $vehicle = Vehicle::factory()->for($contact)->create([
        'make' => 'Ford',
        'model' => 'Puma',
        'year' => 2022,
        'registration' => 'AB22 CDE',
        'price' => '24995.00',
    ]);
    $deal = Deal::factory()->for($rep, 'owner')->for($contact)->for($vehicle)->create([
        'value' => '24000.00',
        'stage' => DealStage::TestDrive,
    ]);

    $this->actingAs($rep)
        ->get(route('deals.show', $deal))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/Show')
            ->where('deal.id', $deal->id)
            ->where('deal.value', '24000.00')
            ->where('deal.stage', 'test_drive')
            ->where('deal.contact.name', 'Ada Lovelace')
            ->where('deal.contact.email', 'ada@example.com')
            ->where('deal.contact.phone', '555-0100')
            ->where('deal.contact.owner.name', 'Riley Rep')
            ->where('deal.vehicle.make', 'Ford')
            ->where('deal.vehicle.model', 'Puma')
            ->where('deal.vehicle.year', 2022)
            ->where('deal.vehicle.registration', 'AB22 CDE')
            ->where('deal.vehicle.price', '24995.00')
            ->has('stages', 5)
        );
});

test('a manager can open another reps deal', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create();

    $this->actingAs($manager)
        ->get(route('deals.show', $deal))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sales/Show')
            ->where('deal.id', $deal->id)
            ->where('deal.vehicle', null)
        );
});

test('a rep gets 403 opening another reps deal', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $deal = Deal::factory()->for($other, 'owner')->create();

    $this->actingAs($rep)
        ->get(route('deals.show', $deal))
        ->assertForbidden();
});

test('guests are redirected from a deal', function () {
    $deal = Deal::factory()->create();

    $this->get(route('deals.show', $deal))
        ->assertRedirect(route('login'));
});

test('marking a deal sold closes the vehicle', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $vehicle = Vehicle::factory()->for($contact)->create([
        'status' => VehicleStatus::Reserved,
    ]);
    $deal = Deal::factory()->for($rep, 'owner')->for($contact)->for($vehicle)->create([
        'stage' => DealStage::Negotiation,
    ]);

    $this->actingAs($rep)
        ->post(route('deals.sold', $deal))
        ->assertRedirect(route('deals.show', $deal));

    expect($deal->refresh()->stage)->toBe(DealStage::Sold)
        ->and($deal->closed_at?->toDateTimeString())->toBe('2026-11-14 12:00:00')
        ->and($vehicle->refresh()->status)->toBe(VehicleStatus::Sold);
});

test('a rep gets 403 marking another reps deal sold', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $deal = Deal::factory()->for($other, 'owner')->create([
        'stage' => DealStage::Negotiation,
    ]);

    $this->actingAs($rep)
        ->post(route('deals.sold', $deal))
        ->assertForbidden();

    expect($deal->refresh()->stage)->toBe(DealStage::Negotiation);
});

test('moving a deal to lost keeps it closed and an open stage clears that', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $rep = User::factory()->rep()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create([
        'stage' => DealStage::Enquiry,
    ]);

    $this->actingAs($rep)
        ->from(route('deals.show', $deal))
        ->patch(route('deals.update', $deal), [
            'stage' => DealStage::Lost->value,
        ])
        ->assertRedirect(route('deals.show', $deal));

    expect($deal->refresh()->closed_at?->toDateTimeString())->toBe('2026-11-14 12:00:00');

    $this->from(route('deals.show', $deal))
        ->patch(route('deals.update', $deal), [
            'stage' => DealStage::Enquiry->value,
        ])
        ->assertRedirect(route('deals.show', $deal));

    expect($deal->refresh()->closed_at)->toBeNull();
});

test('a rep can add a note to their own deal', function () {
    $this->travelTo('2026-11-14 10:30:00');

    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $deal = Deal::factory()->for($rep, 'owner')->for($contact)->create();

    $this->actingAs($rep)
        ->post(route('deals.notes.store', $deal), [
            'body' => 'Test drive scheduled for tomorrow.',
        ])
        ->assertRedirect(route('deals.show', $deal));

    $this->get(route('deals.show', $deal))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('deal.notes.0.body', 'Test drive scheduled for tomorrow.')
            ->where('deal.notes.0.author', 'Riley Rep')
            ->where('deal.notes.0.time', '10:30 AM')
        );
});

test('a rep gets 403 adding a note to another reps deal', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $deal = Deal::factory()->for($other, 'owner')->create();

    $this->actingAs($rep)
        ->post(route('deals.notes.store', $deal), [
            'body' => 'Stolen note',
        ])
        ->assertForbidden();
});

test('the pipeline metrics count open value, conversion, and days to close', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $otherContact = Contact::factory()->for($other, 'owner')->create();

    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '100.00',
        'stage' => DealStage::Enquiry,
    ]);
    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '40.00',
        'stage' => DealStage::TestDrive,
    ]);
    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '10.00',
        'stage' => DealStage::Negotiation,
    ]);
    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '80.00',
        'stage' => DealStage::Sold,
        'created_at' => now()->subDays(10),
        'closed_at' => now(),
    ]);
    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '20.00',
        'stage' => DealStage::Lost,
        'created_at' => now()->subDays(30),
        'closed_at' => now()->subDays(20),
    ]);
    Deal::factory()->for($other, 'owner')->for($otherContact)->create([
        'value' => '999.00',
        'stage' => DealStage::Enquiry,
    ]);

    $this->actingAs($rep)
        ->get(route('deals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('metrics.pipelineValue', '150.00')
            ->where('metrics.openDeals', 3)
            ->where('metrics.conversion', 50)
            ->where('metrics.averageDaysToClose', 10)
            ->has('deals', 5)
        );
});

test('a manager sees pipeline metrics for every rep', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $otherContact = Contact::factory()->for($other, 'owner')->create();

    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '100.00',
        'stage' => DealStage::Enquiry,
    ]);
    Deal::factory()->for($other, 'owner')->for($otherContact)->create([
        'value' => '999.00',
        'stage' => DealStage::Sold,
        'created_at' => now()->subDays(4),
        'closed_at' => now(),
    ]);

    $this->actingAs($manager)
        ->get(route('deals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('metrics.pipelineValue', '100.00')
            ->where('metrics.openDeals', 1)
            ->where('metrics.conversion', 100)
            ->where('metrics.averageDaysToClose', 4)
            ->has('deals', 2)
        );
});

test('guests are redirected from adding a deal note', function () {
    $deal = Deal::factory()->create();

    $this->post(route('deals.notes.store', $deal), [
        'body' => 'Hello',
    ])->assertRedirect(route('login'));
});
