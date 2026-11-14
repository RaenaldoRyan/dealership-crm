<?php

namespace App\Inventory\Policies;

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Reps and managers can open the inventory.
     */
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    /**
     * The inventory lists every vehicle, including other reps' customer vehicles and stock.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $this->isStaff($user);
    }

    /**
     * A vehicle can be added to stock, or to a contact the user may update.
     */
    public function create(User $user, ?Contact $contact = null): bool
    {
        if (! $this->isStaff($user)) {
            return false;
        }

        if (! $contact instanceof Contact) {
            return true;
        }

        return $user->can('update', $contact);
    }

    /**
     * Managers can edit every vehicle. Reps can edit stock and vehicles on their own contacts.
     */
    public function update(User $user, Vehicle $vehicle): bool
    {
        if ($user->role === Role::Manager) {
            return true;
        }

        if ($user->role !== Role::Rep) {
            return false;
        }

        if ($vehicle->contact_id === null) {
            return true;
        }

        $contact = Contact::query()->find($vehicle->contact_id);

        return $contact instanceof Contact && $user->can('update', $contact);
    }

    /**
     * Vehicles are never deleted.
     */
    public function delete(User $user, Vehicle $vehicle): bool
    {
        return false;
    }

    /**
     * Reps and managers are the roles that use the inventory.
     */
    private function isStaff(User $user): bool
    {
        return match ($user->role) {
            Role::Rep, Role::Manager => true,
        };
    }
}
