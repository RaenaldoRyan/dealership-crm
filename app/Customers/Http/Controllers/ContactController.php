<?php

namespace App\Customers\Http\Controllers;

use App\Core\Actions\AddNote;
use App\Core\Models\User;
use App\Customers\Actions\CreateContact;
use App\Customers\Actions\UpdateContact;
use App\Customers\Http\Requests\IndexContactRequest;
use App\Customers\Http\Requests\StoreContactNoteRequest;
use App\Customers\Http\Requests\StoreContactRequest;
use App\Customers\Http\Requests\UpdateContactRequest;
use App\Customers\Models\Contact;
use App\Customers\Queries\ContactDetail;
use App\Customers\Queries\ContactIndex;
use App\Http\Controllers\Controller;
use App\Inventory\Models\Vehicle;
use App\Inventory\Support\Registration;
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
    public function index(IndexContactRequest $request, ContactIndex $contacts): Response
    {
        Gate::authorize('viewAny', Contact::class);

        return Inertia::render('customers/Index', [
            'contacts' => $contacts->paginate(
                $this->actor($request),
                $request->search(),
                $request->filter(),
            ),
            'filters' => [
                'search' => $request->search() ?? '',
                'filter' => $request->filter(),
            ],
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
    public function store(StoreContactRequest $request, CreateContact $create): RedirectResponse
    {
        Gate::authorize('create', Contact::class);

        $create->handle($this->actor($request), $request->contact());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact created.')]);

        return to_route('contacts.index');
    }

    /**
     * Show a contact, with their vehicles and deals.
     */
    public function show(Request $request, Contact $contact, ContactDetail $detail): Response
    {
        Gate::authorize('view', $contact);

        return Inertia::render('customers/Show', [
            'contact' => $detail->present($this->actor($request), $contact),
        ]);
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
    public function update(UpdateContactRequest $request, Contact $contact, UpdateContact $update): RedirectResponse
    {
        Gate::authorize('update', $contact);

        $update->handle($this->actor($request), $contact, $request->contact());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact updated.')]);

        return to_route('contacts.index');
    }

    /**
     * Add a note to a contact.
     */
    public function storeNote(StoreContactNoteRequest $request, Contact $contact, AddNote $add): RedirectResponse
    {
        Gate::authorize('update', $contact);

        $add->handle($this->actor($request), $contact, (string) $request->validated('body'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note added.')]);

        return to_route('contacts.show', $contact);
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
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
            'registration' => Registration::format($vehicle->registration),
            'price' => $vehicle->price,
        ];
    }
}
