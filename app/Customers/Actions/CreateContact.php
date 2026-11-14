<?php

namespace App\Customers\Actions;

use App\Core\Actions\RecordActivity;
use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateContact
{
    public function __construct(private RecordActivity $activity) {}

    /**
     * Store a contact owned by the acting user.
     *
     * A posted user id is ignored.
     *
     * @param  array{name: string, email: string, phone?: string|null}  $attributes
     */
    public function handle(User $actor, array $attributes): Contact
    {
        Gate::forUser($actor)->authorize('create', Contact::class);

        return DB::transaction(function () use ($actor, $attributes): Contact {
            $contact = new Contact;
            $contact->fill($this->details($attributes));
            $contact->owner()->associate($actor);
            $contact->save();

            $this->activity->handle($actor, $contact, ActivityType::ContactCreated, 'Contact created');

            return $contact;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{name: string, email: string, phone?: string|null}
     */
    private function details(array $attributes): array
    {
        $details = [
            'name' => $attributes['name'],
            'email' => $attributes['email'],
        ];

        if (array_key_exists('phone', $attributes)) {
            $details['phone'] = $attributes['phone'];
        }

        return $details;
    }
}
