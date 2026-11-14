<?php

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('overview'))
        ->assertRedirect(route('login'));
});

test('a rep sees only their own overview', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $other = User::factory()->rep()->create(['name' => 'Other Rep']);
    $contact = Contact::factory()->for($rep, 'owner')->create(['name' => 'Ada Lovelace']);
    $otherContact = Contact::factory()->for($other, 'owner')->create(['name' => 'Other Contact']);

    Vehicle::factory()->for($contact)->create(['price' => '10000.00']);
    $soldVehicle = Vehicle::factory()->for($contact)->create(['price' => '30000.00']);
    Vehicle::factory()->for($otherContact)->create(['price' => '5000.00']);

    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '100.00',
        'stage' => DealStage::Enquiry,
    ]);
    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '50.00',
        'stage' => DealStage::Enquiry,
    ]);
    Deal::factory()->for($rep, 'owner')->for($contact)->for($soldVehicle)->create([
        'value' => '80.00',
        'stage' => DealStage::Sold,
        'created_at' => now()->subDays(10),
        'closed_at' => now(),
    ]);
    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '20.00',
        'stage' => DealStage::Lost,
    ]);
    Deal::factory()->for($other, 'owner')->for($otherContact)->create([
        'value' => '999.00',
        'stage' => DealStage::Enquiry,
    ]);

    $this->actingAs($rep)
        ->get(route('overview', ['rep' => $other->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('overview/Index')
            ->where('canFilterReps', false)
            ->where('reps', [])
            ->where('selectedRep', null)
            ->where('metrics.pipelineValue', '150.00')
            ->where('metrics.openDeals', 2)
            ->where('metrics.conversion', 50)
            ->where('metrics.averageDaysToClose', 10)
            ->where('stages.0', ['value' => 'enquiry', 'label' => 'Enquiry', 'count' => 2])
            ->where('stages.1.count', 0)
            ->where('stages.2.count', 0)
            ->where('stages.3.count', 1)
            ->where('stages.4.count', 1)
            ->where('inventory.inStock', 1)
            ->where('inventory.linkedToDeals', 1)
            ->where('inventory.averagePrice', '20000.00')
            ->has('recentDeals', 4)
            ->where('recentDeals.0.contact', 'Ada Lovelace')
            ->where('recentDeals.0.stage', 'lost')
            ->where('recentDeals.0.stageLabel', 'Lost')
        );
});

test('a manager sees every rep and can filter to one', function () {
    $manager = User::factory()->manager()->create();
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $other = User::factory()->rep()->create(['name' => 'Other Rep']);
    $contact = Contact::factory()->for($rep, 'owner')->create(['name' => 'Ada Lovelace']);
    $otherContact = Contact::factory()->for($other, 'owner')->create();

    Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'value' => '100.00',
        'stage' => DealStage::Enquiry,
    ]);
    Deal::factory()->for($other, 'owner')->for($otherContact)->create([
        'value' => '999.00',
        'stage' => DealStage::Enquiry,
    ]);

    $this->actingAs($manager)
        ->get(route('overview'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('canFilterReps', true)
            ->where('metrics.pipelineValue', '1099.00')
            ->where('metrics.openDeals', 2)
            ->has('reps', 2)
        );

    $this->actingAs($manager)
        ->get(route('overview', ['rep' => $rep->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedRep', $rep->id)
            ->where('metrics.pipelineValue', '100.00')
            ->where('metrics.openDeals', 1)
            ->where('recentDeals.0.contact', 'Ada Lovelace')
        );
});

test('a manager cannot filter the overview to someone who is not a rep', function () {
    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('overview', ['rep' => $manager->id]))
        ->assertSessionHasErrors('rep');
});

test('marking a deal sold records when it closed', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $deal = Deal::factory()->for($rep, 'owner')->for($contact)->create([
        'stage' => DealStage::Enquiry,
        'created_at' => now()->subDays(4),
    ]);

    $this->actingAs($rep)
        ->from(route('deals.index'))
        ->patch(route('deals.update', $deal), ['stage' => DealStage::Sold->value])
        ->assertRedirect(route('deals.index'));

    expect($deal->refresh()->closed_at?->toDateTimeString())->toBe('2026-11-14 12:00:00');

    $this->from(route('deals.index'))
        ->patch(route('deals.update', $deal), ['stage' => DealStage::Lost->value])
        ->assertRedirect(route('deals.index'));

    expect($deal->refresh()->closed_at?->toDateTimeString())->toBe('2026-11-14 12:00:00');

    $this->from(route('deals.index'))
        ->patch(route('deals.update', $deal), ['stage' => DealStage::Enquiry->value])
        ->assertRedirect(route('deals.index'));

    expect($deal->refresh()->closed_at)->toBeNull();
});
