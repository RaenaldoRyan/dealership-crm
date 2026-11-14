<?php

namespace App\Inventory\Http\Controllers;

use App\Customers\Models\Contact;
use App\Http\Controllers\Controller;
use App\Inventory\Http\Requests\StoreVehicleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class VehicleController extends Controller
{
    /**
     * Add a vehicle to a contact the user is allowed to manage.
     */
    public function store(StoreVehicleRequest $request, Contact $contact): RedirectResponse
    {
        $contact->vehicles()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle added.')]);

        return to_route('contacts.edit', $contact);
    }
}
