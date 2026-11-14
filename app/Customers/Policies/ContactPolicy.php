<?php

namespace App\Customers\Policies;

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Customers\Models\Contact;

class ContactPolicy
{
    /**
     * Determine whether the user can view any contacts.
     */
    public function viewAny(User $user): bool
    {
        return $this->worksWithContacts($user);
    }

    /**
     * Determine whether the user can view the contact.
     */
    public function view(User $user, Contact $contact): bool
    {
        return $this->canManage($user, $contact);
    }

    /**
     * Determine whether the user can create contacts.
     */
    public function create(User $user): bool
    {
        return $this->worksWithContacts($user);
    }

    /**
     * Determine whether the user can update the contact.
     */
    public function update(User $user, Contact $contact): bool
    {
        return $this->canManage($user, $contact);
    }

    /**
     * Reps and managers are the roles that use the contact book.
     */
    private function worksWithContacts(User $user): bool
    {
        return match ($user->role) {
            Role::Rep, Role::Manager => true,
        };
    }

    /**
     * Managers can manage every contact. Reps can manage their own.
     */
    private function canManage(User $user, Contact $contact): bool
    {
        return match ($user->role) {
            Role::Manager => true,
            Role::Rep => $contact->user_id === $user->id,
        };
    }
}
