<?php

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Sales\Models\Deal;

test('guests are redirected from search', function () {
    $this->get(route('search', ['q' => 'Ada']))
        ->assertRedirect(route('login'));
});

test('search is grouped and limited to records the viewer can open', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $own = Contact::factory()->for($rep, 'owner')->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'phone' => '07700 900111',
    ]);
    Contact::factory()->for($other, 'owner')->create([
        'name' => 'Ada Hidden',
        'email' => 'hidden@example.com',
    ]);
    $vehicle = Vehicle::factory()->for($own)->create([
        'make' => 'Ford',
        'model' => 'Puma',
        'registration' => 'AB22 CDE',
    ]);
    $deal = Deal::factory()->for($rep, 'owner')->for($own)->for($vehicle)->create();
    Deal::factory()->for($other, 'owner')->for(
        Contact::factory()->for($other, 'owner')->create(['name' => 'Ada Other']),
    )->create();

    $this->actingAs($rep)
        ->getJson(route('search', ['q' => 'ada']))
        ->assertOk()
        ->assertJsonCount(1, 'contacts')
        ->assertJsonPath('contacts.0.label', 'Ada Lovelace')
        ->assertJsonPath('contacts.0.detail', 'ada@example.com')
        ->assertJsonPath('contacts.0.href', route('contacts.show', $own))
        ->assertJsonCount(1, 'deals')
        ->assertJsonPath('deals.0.label', 'Ada Lovelace')
        ->assertJsonPath('deals.0.href', route('deals.show', $deal));

    $this->actingAs($rep)
        ->getJson(route('search', ['q' => 'AB22 CDE']))
        ->assertOk()
        ->assertJsonCount(1, 'vehicles')
        ->assertJsonPath('vehicles.0.label', 'AB22 CDE')
        ->assertJsonPath('vehicles.0.detail', 'Ford Puma')
        ->assertJsonPath('vehicles.0.href', route('vehicles.show', $vehicle))
        ->assertJsonCount(1, 'deals');

    $this->actingAs($rep)
        ->getJson(route('search'))
        ->assertOk()
        ->assertExactJson([
            'contacts' => [],
            'deals' => [],
            'vehicles' => [],
        ]);
});

test('search returns at most five matches in a group', function () {
    $rep = User::factory()->rep()->create();
    Contact::factory()->for($rep, 'owner')->count(6)->create([
        'name' => 'Ada Match',
    ]);

    $this->actingAs($rep)
        ->getJson(route('search', ['q' => 'Ada']))
        ->assertOk()
        ->assertJsonCount(5, 'contacts');
});
