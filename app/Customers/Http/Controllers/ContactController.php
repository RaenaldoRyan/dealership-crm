<?php

namespace App\Customers\Http\Controllers;

use App\Customers\Http\Requests\StoreContactRequest;
use App\Customers\Http\Requests\UpdateContactRequest;
use App\Customers\Models\Contact;
use App\Http\Controllers\Controller;
use App\Inventory\Models\Vehicle;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Show the contacts the current user is allowed to see.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Contact::class);

        $contacts = Contact::query()
            ->visibleTo($request->user())
            ->with('owner:id,name')
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Contact $contact): array => $this->present($contact))
            ->all();

        return Inertia::render('customers/Index', [
            'contacts' => $contacts,
        ]);
    }

    /**
     * Show the form for creating a contact.
     */
    public function create(): Response
    {
        Gate::authorize('create', Contact::class);

        return Inertia::render('customers/Create');
    }

    /**
     * Store a contact owned by the current user.
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $request->user()->contacts()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact created.')]);

        return to_route('contacts.index');
    }

    /**
     * Show the form for editing a contact.
     */
    public function edit(Contact $contact): Response
    {
        Gate::authorize('update', $contact);

        $contact->load([
            'owner:id,name',
            'vehicles' => fn (HasMany $query) => $query->latest()->orderByDesc('id'),
        ]);

        return Inertia::render('customers/Edit', [
            'contact' => $this->present($contact),
            'vehicles' => $contact->vehicles
                ->map(fn (Vehicle $vehicle): array => $this->presentVehicle($vehicle))
                ->all(),
        ]);
    }

    /**
     * Update a contact without changing its owner.
     */
    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact updated.')]);

        return to_route('contacts.index');
    }

    /**
     * @return array{id: int, name: string, email: string, phone: string|null, owner: array{name: string}}
     */
    private function present(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'owner' => [
                'name' => $contact->owner->name,
            ],
        ];
    }

    /**
     * @return array{id: int, make: string, model: string, year: int, registration: string, price: string}
     */
    private function presentVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'registration' => $vehicle->registration,
            'price' => $vehicle->price,
        ];
    }
}
