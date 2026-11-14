<?php

namespace App\Inventory\Policies;

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;

class VehiclePolicy
{
    /**
     * Determine whether the user can view the vehicle.
     *
     * Access follows the contact's owner.
     */
    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can('view', $vehicle->contact);
    }

    /**
     * Determine whether the user can add a vehicle to the contact.
     *
     * Access follows the contact's owner.
     */
    public function create(User $user, Contact $contact): bool
    {
        return $user->can('update', $contact);
    }
}
