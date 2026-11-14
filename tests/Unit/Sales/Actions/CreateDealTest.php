<?php

use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Sales\Actions\CreateDeal;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Auth\Access\AuthorizationException;

it('opens a deal owned by the acting user', function () {
    $rep = User::factory()->rep()->create();
    $manager = User::factory()->manager()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();

    $deal = app(CreateDeal::class)->handle($rep, [
        'contact_id' => $contact->id,
        'value' => '15000.00',
        'stage' => DealStage::Enquiry->value,
        'user_id' => $manager->id,
    ]);

    expect($deal->user_id)->toBe($rep->id)
        ->and($deal->contact_id)->toBe($contact->id)
        ->and($deal->value)->toBe('15000.00')
        ->and($deal->stage)->toBe(DealStage::Enquiry)
        ->and($deal->closed_at)->toBeNull();

    $this->assertDatabaseHas('activities', [
        'subject_type' => Deal::class,
        'subject_id' => $deal->id,
        'user_id' => $rep->id,
        'type' => ActivityType::DealCreated->value,
        'description' => 'Deal created',
    ]);
});

it('forbids a rep from opening a deal on another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();

    expect(fn () => app(CreateDeal::class)->handle($rep, [
        'contact_id' => $contact->id,
        'value' => '15000.00',
        'stage' => DealStage::Enquiry->value,
    ]))->toThrow(AuthorizationException::class);

    expect(Deal::query()->count())->toBe(0);
});
