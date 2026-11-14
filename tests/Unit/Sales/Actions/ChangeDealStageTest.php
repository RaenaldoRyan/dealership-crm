<?php

use App\Core\Enums\ActivityType;
use App\Core\Models\Activity;
use App\Core\Models\User;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use App\Sales\Actions\ChangeDealStage;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Auth\Access\AuthorizationException;

it('records a close time when a deal is lost', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $rep = User::factory()->rep()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create([
        'stage' => DealStage::Enquiry,
    ]);

    app(ChangeDealStage::class)->handle($rep, $deal, DealStage::Lost);

    expect($deal->refresh()->closed_at?->toDateTimeString())->toBe('2026-11-14 12:00:00')
        ->and($deal->stage)->toBe(DealStage::Lost);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Deal::class,
        'subject_id' => $deal->id,
        'type' => ActivityType::StageChanged->value,
        'description' => 'Stage changed from Enquiry to Lost',
    ]);
});

it('does not record activity when the stage stays the same', function () {
    $rep = User::factory()->rep()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create([
        'stage' => DealStage::Enquiry,
    ]);

    app(ChangeDealStage::class)->handle($rep, $deal, DealStage::Enquiry);

    expect(Activity::query()->count())->toBe(0);
});

it('clears the close time when a deal returns to an open stage', function () {
    $rep = User::factory()->rep()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create([
        'stage' => DealStage::Lost,
        'closed_at' => now(),
    ]);

    app(ChangeDealStage::class)->handle($rep, $deal, DealStage::Enquiry);

    expect($deal->refresh()->closed_at)->toBeNull()
        ->and($deal->stage)->toBe(DealStage::Enquiry);
});

it('keeps the vehicle status when the stage select marks a deal sold', function () {
    $this->travelTo('2026-11-14 12:00:00');

    $rep = User::factory()->rep()->create();
    $vehicle = Vehicle::factory()->create([
        'status' => VehicleStatus::InStock,
    ]);
    $deal = Deal::factory()->for($rep, 'owner')->for($vehicle)->create([
        'stage' => DealStage::Negotiation,
    ]);

    app(ChangeDealStage::class)->handle($rep, $deal, DealStage::Sold);

    expect($deal->refresh()->closed_at?->toDateTimeString())->toBe('2026-11-14 12:00:00')
        ->and($vehicle->refresh()->status)->toBe(VehicleStatus::InStock);
});

it('forbids a rep from changing another reps deal', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $deal = Deal::factory()->for($other, 'owner')->create([
        'stage' => DealStage::Enquiry,
    ]);

    expect(fn () => app(ChangeDealStage::class)->handle($rep, $deal, DealStage::Lost))
        ->toThrow(AuthorizationException::class);

    expect($deal->refresh()->stage)->toBe(DealStage::Enquiry);
});
