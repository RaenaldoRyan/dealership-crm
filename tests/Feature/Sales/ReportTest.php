<?php

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('reports.index'))
        ->assertRedirect(route('login'));

    $this->get(route('reports.export'))
        ->assertRedirect(route('login'));
});

test('a rep cannot open reports', function () {
    $rep = User::factory()->rep()->create();

    $this->actingAs($rep)
        ->get(route('reports.index'))
        ->assertForbidden();

    $this->actingAs($rep)
        ->get(route('reports.export'))
        ->assertForbidden();
});

test('the report counts sales by close date and open deals by created date', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $manager = User::factory()->manager()->create();
    $riley = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $other = User::factory()->rep()->create(['name' => 'Other Rep']);
    User::factory()->rep()->inactive()->create(['name' => 'Quiet Rep']);
    $contact = Contact::factory()->for($riley, 'owner')->create();
    $otherContact = Contact::factory()->for($other, 'owner')->create();

    Deal::factory()->for($riley, 'owner')->for($contact)->create([
        'value' => '100.00',
        'stage' => DealStage::Sold,
        'created_at' => now()->subDays(40),
        'closed_at' => now(),
    ]);
    Deal::factory()->for($riley, 'owner')->for($contact)->create([
        'value' => '80.00',
        'stage' => DealStage::Sold,
        'closed_at' => now(),
    ]);
    Deal::factory()->for($riley, 'owner')->for($contact)->create([
        'value' => '50.00',
        'stage' => DealStage::Sold,
        'created_at' => now()->subDays(40),
        'closed_at' => now()->subDays(40),
    ]);
    Deal::factory()->for($riley, 'owner')->for($contact)->create([
        'value' => '25.00',
        'stage' => DealStage::Sold,
        'closed_at' => now()->subDays(120),
    ]);
    Deal::factory()->for($riley, 'owner')->for($contact)->create([
        'value' => '20.00',
        'stage' => DealStage::Lost,
        'closed_at' => now(),
    ]);
    Deal::factory()->for($other, 'owner')->for($otherContact)->create([
        'value' => '999.00',
        'stage' => DealStage::Enquiry,
    ]);
    Deal::factory()->for($other, 'owner')->for($otherContact)->create([
        'value' => '10.00',
        'stage' => DealStage::Enquiry,
        'created_at' => now()->subDays(40),
    ]);

    $this->actingAs($manager)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/Index')
            ->where('range', '30')
            ->where('reps.0.name', 'Other Rep')
            ->where('reps.0.sold', 0)
            ->where('reps.0.value', '0.00')
            ->where('reps.0.conversion', 0)
            ->where('reps.1.name', 'Quiet Rep')
            ->where('reps.1.sold', 0)
            ->where('reps.2.name', 'Riley Rep')
            ->where('reps.2.sold', 2)
            ->where('reps.2.value', '180.00')
            ->where('reps.2.conversion', 67)
            ->where('stages.0.count', 1)
            ->where('stages.3.count', 2)
            ->where('stages.4.count', 1)
        );

    $this->get(route('reports.index', ['range' => '90']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('range', '90')
            ->where('reps.2.sold', 3)
            ->where('reps.2.value', '230.00')
            ->where('reps.2.conversion', 75)
            ->where('stages.0.count', 2)
            ->where('stages.3.count', 3)
        );

    $this->get(route('reports.index', ['range' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('range', 'all')
            ->where('reps.2.sold', 4)
            ->where('reps.2.value', '255.00')
            ->where('reps.2.conversion', 80)
        );

    $this->get(route('reports.index', ['range' => 'nope']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('range', '30')
            ->where('reps.2.sold', 2)
        );
});

test('the csv export matches the sales table', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $manager = User::factory()->manager()->create();
    $riley = User::factory()->rep()->create(['name' => '=Riley Rep']);
    $contact = Contact::factory()->for($riley, 'owner')->create();

    Deal::factory()->for($riley, 'owner')->for($contact)->create([
        'value' => '100.00',
        'stage' => DealStage::Sold,
        'closed_at' => now(),
    ]);

    $response = $this->actingAs($manager)
        ->get(route('reports.export', ['range' => '30']));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertHeader('content-disposition', 'attachment; filename="sales-report.csv"');

    $csv = $response->getContent();

    expect($csv)
        ->toContain('Rep,Sold,Value,Conversion')
        ->toContain("'=Riley Rep")
        ->toContain('1,100.00,100%')
        ->not->toContain("\n=Riley Rep");
});

test('a manager who sold a deal is included', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $manager = User::factory()->manager()->create(['name' => 'Morgan Hale']);
    $contact = Contact::factory()->for($manager, 'owner')->create();

    Deal::factory()->for($manager, 'owner')->for($contact)->create([
        'value' => '40.00',
        'stage' => DealStage::Sold,
        'closed_at' => now(),
    ]);

    $this->actingAs($manager)
        ->get(route('reports.index', ['range' => 'all']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('reps', function (mixed $reps): bool {
                $row = collect($reps)->firstWhere('name', 'Morgan Hale');

                return is_array($row)
                    && $row['sold'] === 1
                    && $row['value'] === '40.00'
                    && $row['conversion'] === 100;
            })
        );
});
