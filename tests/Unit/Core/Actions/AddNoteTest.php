<?php

use App\Core\Actions\AddNote;
use App\Core\Enums\ActivityType;
use App\Core\Models\Note;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Sales\Models\Deal;
use Illuminate\Auth\Access\AuthorizationException;

it('adds a note to a contact and records the activity', function () {
    $rep = User::factory()->rep()->create(['name' => 'Riley Rep']);
    $contact = Contact::factory()->for($rep, 'owner')->create();

    $note = app(AddNote::class)->handle($rep, $contact, 'Called about the test drive.');

    expect($note->body)->toBe('Called about the test drive.')
        ->and($note->user_id)->toBe($rep->id)
        ->and($note->notable_type)->toBe(Contact::class)
        ->and($note->notable_id)->toBe($contact->id);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Contact::class,
        'subject_id' => $contact->id,
        'user_id' => $rep->id,
        'type' => ActivityType::NoteAdded->value,
        'description' => 'Note added',
    ]);
});

it('adds a note to a deal the actor owns', function () {
    $rep = User::factory()->rep()->create();
    $deal = Deal::factory()->for($rep, 'owner')->create();

    $note = app(AddNote::class)->handle($rep, $deal, 'Customer wants a paint inspection.');

    expect($note->notable_type)->toBe(Deal::class)
        ->and($note->notable_id)->toBe($deal->id);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Deal::class,
        'subject_id' => $deal->id,
        'description' => 'Note added',
    ]);
});

it('forbids a rep from noting another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();

    expect(fn () => app(AddNote::class)->handle($rep, $contact, 'Stolen note'))
        ->toThrow(AuthorizationException::class);

    expect(Note::query()->count())->toBe(0);
});

it('forbids a rep from noting another reps deal', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $deal = Deal::factory()->for($other, 'owner')->create();

    expect(fn () => app(AddNote::class)->handle($rep, $deal, 'Stolen note'))
        ->toThrow(AuthorizationException::class);

    expect(Note::query()->count())->toBe(0);
});
