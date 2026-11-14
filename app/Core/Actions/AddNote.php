<?php

namespace App\Core\Actions;

use App\Core\Enums\ActivityType;
use App\Core\Models\Note;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Sales\Models\Deal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AddNote
{
    public function __construct(private RecordActivity $activity) {}

    /**
     * Add a note to a contact or deal the actor is allowed to update.
     */
    public function handle(User $actor, Contact|Deal $notable, string $body): Note
    {
        Gate::forUser($actor)->authorize('update', $notable);

        return DB::transaction(function () use ($actor, $notable, $body): Note {
            $note = new Note;
            $note->body = $body;
            $note->notable()->associate($notable);
            $note->author()->associate($actor);
            $note->save();

            $this->activity->handle($actor, $notable, ActivityType::NoteAdded, 'Note added');

            return $note;
        });
    }
}
