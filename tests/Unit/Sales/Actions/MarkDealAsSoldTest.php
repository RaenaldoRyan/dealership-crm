<?php

use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use App\Sales\Actions\MarkDealAsSold;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Auth\Access\AuthorizationException;

it('closes the deal and marks its vehicle sold', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $rep = User::factory()->rep()->create();
    $vehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::Reserved,
    ]);
    $deal = Deal::factory()->for($rep, 'owner')->for($vehicle)->create([
        'stage' => DealStage::Negotiation,
    ]);

    app(MarkDealAsSold::class)->handle($rep, $deal);

    expect($deal->refresh()->stage)->toBe(DealStage::Sold)
        ->and($deal->closed_at?->toDateTimeString())->toBe('2026-11-14 12:00:00')
        ->and($vehicle->refresh()->status)->toBe(VehicleStatus::Sold);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Deal::class,
        'subject_id' => $deal->id,
        'user_id' => $rep->id,
        'type' => ActivityType::DealSold->value,
        'description' => 'Deal marked as sold',
    ]);
});

it('closes a deal that has no vehicle', function () {
    $rep = User::factory()->rep()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create([
        'vehicle_id' => null,
        'stage' => DealStage::Enquiry,
    ]);

    app(MarkDealAsSold::class)->handle($rep, $deal);

    expect($deal->refresh()->stage)->toBe(DealStage::Sold)
        ->and($deal->closed_at)->not->toBeNull();
});

it('forbids a rep from marking another reps deal sold', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $vehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::InStock,
    ]);
    $deal = Deal::factory()->for($other, 'owner')->for($vehicle)->create([
        'stage' => DealStage::Negotiation,
    ]);

    expect(fn () => app(MarkDealAsSold::class)->handle($rep, $deal))
        ->toThrow(AuthorizationException::class);

    expect($deal->refresh()->stage)->toBe(DealStage::Negotiation)
        ->and($vehicle->refresh()->status)->toBe(VehicleStatus::InStock);
});
