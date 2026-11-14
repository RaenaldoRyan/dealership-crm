<?php

namespace App\Customers\Actions;

use App\Core\Models\User;
use App\Customers\Models\Contact;
use Illuminate\Support\Facades\Gate;

class UpdateContact
{
    /**
     * Update a contact without changing its owner.
     *
     * @param  array{name: string, email: string, phone?: string|null}  $attributes
     */
    public function handle(User $actor, Contact $contact, array $attributes): Contact
    {
        Gate::forUser($actor)->authorize('update', $contact);

        $contact->fill($this->details($attributes));
        $contact->save();

        return $contact;
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
