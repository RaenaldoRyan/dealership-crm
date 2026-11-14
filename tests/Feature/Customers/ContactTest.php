<?php

use App\Core\Actions\AddNote;
use App\Core\Models\User;
use App\Customers\Actions\CreateContact;
use App\Customers\Models\Contact;
use App\Inventory\Actions\CreateVehicle;
use App\Inventory\Models\Vehicle;
use App\Sales\Actions\ChangeDealStage;
use App\Sales\Actions\CreateDeal;
use App\Sales\Actions\MarkDealAsSold;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
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
            ->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'Own Contact')
            ->where('contacts.data.0.email', 'own@example.com')
            ->where('contacts.data.0.owner', 'RR')
            ->where('contacts.data.0.vehicles', 0)
            ->where('contacts.data.0.openDeals', 0)
            ->where('contacts.data.0.lastActivity', null)
            ->where('filters.filter', 'all')
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
            ->has('contacts.data', 2)
            ->where('contacts.data', function (mixed $contacts): bool {
                $emails = collect($contacts)->pluck('email')->sort()->values()->all();
                $owners = collect($contacts)->pluck('owner')->sort()->values()->all();

                return $emails === ['other@example.com', 'own@example.com']
                    && $owners === ['OR', 'RR'];
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

test('a rep can open their own contact', function () {
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $manager = User::factory()->manager()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '555-0199',
    ]);
    Vehicle::factory()->for($contact)->create([
        'make' => 'Ford',
        'model' => 'Puma',
        'year' => 2022,
        'registration' => 'AB22 CDE',
        'price' => '18950',
    ]);
    Deal::factory()->for($contact)->create([
        'user_id' => $rep->id,
        'stage' => DealStage::TestDrive,
        'value' => '18950',
    ]);
    Deal::factory()->for($contact)->create([
        'user_id' => $manager->id,
        'stage' => DealStage::Lost,
        'value' => '1000',
    ]);

    $this->actingAs($rep)
        ->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/Show')
            ->where('contact.name', 'Ada Lovelace')
            ->where('contact.email', 'ada@example.com')
            ->where('contact.phone', '555-0199')
            ->where('contact.owner.name', 'Riley Rep')
            ->has('contact.vehicles', 1)
            ->where('contact.vehicles.0.registration', 'AB22 CDE')
            ->has('contact.deals', 1)
            ->where('contact.deals.0.stage', 'test_drive')
            ->where('contact.deals.0.stageLabel', 'Test drive')
        );
});

test('a manager sees another reps deals on the contact', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $vehicle = Vehicle::factory()->for($contact)->create([
        'make' => 'Ford',
        'model' => 'Puma',
    ]);

    Deal::factory()->for($contact)->create([
        'user_id' => $rep->id,
        'vehicle_id' => $vehicle->id,
        'stage' => DealStage::Enquiry,
        'value' => '1000',
    ]);

    $this->actingAs($manager)
        ->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/Show')
            ->has('contact.deals', 1)
            ->where('contact.deals.0.vehicle', 'Ford Puma')
        );
});

test('a rep gets 403 on another reps contact page', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();

    $this->actingAs($rep)
        ->get(route('contacts.show', $contact))
        ->assertForbidden();
});

test('guests are redirected from a contact page', function () {
    $contact = Contact::factory()->create();

    $this->get(route('contacts.show', $contact))
        ->assertRedirect(route('login'));
});

test('a rep can add a note to their own contact', function () {
    $this->travelTo('2026-11-13 15:00:00');

    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $contact = Contact::factory()->for($rep, 'owner')->create();

    app(AddNote::class)->handle($rep, $contact, 'Older note');

    $this->travelTo('2026-11-14 10:30:00');

    $this->actingAs($rep)
        ->post(route('contacts.notes.store', $contact), [
            'body' => 'Called about the test drive.',
        ])
        ->assertRedirect(route('contacts.show', $contact));

    $this->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.notes.0.body', 'Called about the test drive.')
            ->where('contact.notes.0.author', 'Riley Rep')
            ->where('contact.notes.0.time', '10:30 AM')
            ->where('contact.notes.1.body', 'Older note')
            ->where('contact.notes.1.time', 'Yesterday')
        );
});

test('a note requires a body', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();

    $this->actingAs($rep)
        ->from(route('contacts.show', $contact))
        ->post(route('contacts.notes.store', $contact), [
            'body' => '',
        ])
        ->assertSessionHasErrors([
            'body' => 'The body field is required.',
        ])
        ->assertRedirect(route('contacts.show', $contact));

    $this->actingAs($rep)
        ->from(route('contacts.show', $contact))
        ->post(route('contacts.notes.store', $contact), [
            'body' => str_repeat('a', 5001),
        ])
        ->assertSessionHasErrors([
            'body' => 'The body field must not be greater than 5000 characters.',
        ]);
});

test('a rep gets 403 adding a note to another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();

    $this->actingAs($rep)
        ->post(route('contacts.notes.store', $contact), [
            'body' => 'Stolen note',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('notes', 0);
});

test('the contact feed shows the contact and its visible deals, newest first', function () {
    $this->travelTo('2026-11-14 09:00:00');

    $rep = User::factory()->rep()->create();
    $manager = User::factory()->manager()->create();
    $contact = app(CreateContact::class)->handle($rep, [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $this->travelTo('2026-11-14 10:00:00');

    app(CreateVehicle::class)->handle($rep, [
        'make' => 'Ford',
        'model' => 'Puma',
        'year' => 2022,
        'registration' => 'AB22 CDE',
        'price' => '18000',
    ], $contact);

    $this->travelTo('2026-11-14 11:00:00');

    $deal = app(CreateDeal::class)->handle($rep, [
        'contact_id' => $contact->id,
        'value' => '18000',
        'stage' => DealStage::Enquiry->value,
    ]);

    $this->travelTo('2026-11-14 12:00:00');

    app(ChangeDealStage::class)->handle($rep, $deal, DealStage::Negotiation);

    $this->travelTo('2026-11-14 13:00:00');

    app(AddNote::class)->handle($rep, $contact, 'Left a voicemail.');

    $this->travelTo('2026-11-14 14:00:00');

    app(MarkDealAsSold::class)->handle($rep, $deal);

    $this->travelTo('2026-11-14 15:00:00');

    $hidden = app(CreateDeal::class)->handle($manager, [
        'contact_id' => $contact->id,
        'value' => '1',
        'stage' => DealStage::Enquiry->value,
    ]);

    $this->actingAs($rep)
        ->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.activity.0.description', 'Deal marked as sold')
            ->where('contact.activity.1.description', 'Note added')
            ->where('contact.activity.2.description', 'Stage changed from Enquiry to Negotiation')
            ->where('contact.activity.3.description', 'Deal created')
            ->where('contact.activity.4.description', 'Vehicle AB22 CDE added')
            ->where('contact.activity.5.description', 'Contact created')
            ->has('contact.activity', 6)
        );

    $this->actingAs($manager)
        ->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.activity.0.description', 'Deal created')
            ->has('contact.activity', 7)
            ->where('contact.deals', function (mixed $deals) use ($hidden): bool {
                return collect($deals)->pluck('id')->contains($hidden->id);
            })
        );
});

test('the contact list counts vehicles and open deals', function () {
    $this->travelTo('2026-11-13 12:00:00');

    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $manager = User::factory()->manager()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create([
        'name' => 'Ada Lovelace',
        'phone' => '07700 900123',
    ]);
    $quiet = Contact::factory()->for($rep, 'owner')->create(['name' => 'Quiet Contact']);

    Vehicle::factory()->for($contact)->count(2)->create();
    Deal::factory()->for($rep, 'owner')->for($contact)->create(['stage' => DealStage::Enquiry]);
    Deal::factory()->for($rep, 'owner')->for($contact)->create(['stage' => DealStage::Sold]);
    Deal::factory()->for($rep, 'owner')->for($contact)->create(['stage' => DealStage::Lost]);
    Deal::factory()->for($manager, 'owner')->for($contact)->create(['stage' => DealStage::Negotiation]);
    Deal::factory()->for($rep, 'owner')->for($quiet)->create(['stage' => DealStage::Sold]);

    app(AddNote::class)->handle($rep, $contact, 'Left a voicemail.');

    $this->travelTo('2026-11-14 10:30:00');

    $this->actingAs($rep)
        ->get(route('contacts.index', ['filter' => 'open', 'search' => '07700']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'Ada Lovelace')
            ->where('contacts.data.0.vehicles', 2)
            ->where('contacts.data.0.openDeals', 1)
            ->where('contacts.data.0.owner', 'RR')
            ->where('contacts.data.0.lastActivity', 'Yesterday')
            ->where('filters.filter', 'open')
            ->where('filters.search', '07700')
        );

    $this->actingAs($manager)
        ->get(route('contacts.index', ['search' => 'Ada']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('contacts.data.0.openDeals', 2)
        );
});

test('a manager can limit the contact list to their own', function () {
    $manager = User::factory()->manager()->create(['name' => 'Morgan Hale']);
    $rep = User::factory()->rep()->create();
    Contact::factory()->for($manager, 'owner')->create(['name' => 'Mine']);
    Contact::factory()->for($rep, 'owner')->create(['name' => 'Theirs']);

    $this->actingAs($manager)
        ->get(route('contacts.index', ['filter' => 'mine']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'Mine')
            ->where('contacts.data.0.owner', 'MH')
            ->where('filters.filter', 'mine')
        );
});

test('the contact list pages eight at a time and keeps the search', function () {
    $rep = User::factory()->rep()->create();
    Contact::factory()->for($rep, 'owner')->count(9)->create([
        'name' => 'Ada Page',
    ]);

    $this->actingAs($rep)
        ->get(route('contacts.index', ['search' => 'Ada']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts.data', 8)
            ->where('contacts.total', 9)
            ->where('contacts.from', 1)
            ->where('contacts.to', 8)
            ->where('contacts.next_page_url', fn (mixed $url): bool => is_string($url) && str_contains($url, 'search=Ada'))
        );

    $this->actingAs($rep)
        ->get(route('contacts.index', ['search' => 'Ada', 'page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts.data', 1)
            ->where('contacts.from', 9)
            ->where('contacts.to', 9)
        );
});

test('a contact search treats percent signs as literal text', function () {
    $rep = User::factory()->rep()->create();
    Contact::factory()->for($rep, 'owner')->create(['name' => 'Ada Lovelace']);

    $this->actingAs($rep)
        ->get(route('contacts.index', ['search' => '%']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('contacts.data', 0)
            ->where('contacts.total', 0)
        );
});
