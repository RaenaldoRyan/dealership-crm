<?php

namespace App\Inventory\Http\Controllers;

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Http\Controllers\Controller;
use App\Inventory\Actions\CreateVehicle;
use App\Inventory\Actions\UpdateVehicle;
use App\Inventory\Enums\FuelType;
use App\Inventory\Enums\Transmission;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Http\Requests\IndexVehicleRequest;
use App\Inventory\Http\Requests\SaveVehicleRequest;
use App\Inventory\Http\Requests\StoreVehicleRequest;
use App\Inventory\Http\Requests\UpdateVehicleRequest;
use App\Inventory\Models\Vehicle;
use App\Inventory\Queries\VehicleIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    /**
     * List stock and customer vehicles.
     */
    public function index(IndexVehicleRequest $request, VehicleIndex $vehicles): Response
    {
        Gate::authorize('viewAny', Vehicle::class);

        return Inertia::render('inventory/Index', [
            'vehicles' => $vehicles->paginate($request->search(), $request->status()),
            'filters' => [
                'search' => $request->search() ?? '',
                'status' => $request->status()?->value ?? '',
            ],
            'statuses' => $this->options(VehicleStatus::cases()),
        ]);
    }

    /**
     * Show the form for recording a vehicle.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Vehicle::class);

        $user = $this->actor($request);

        return Inertia::render('inventory/Create', [
            ...$this->formOptions($user),
            'contactId' => $this->prefilledContact($request, $user),
        ]);
    }

    /**
     * Store a vehicle from the inventory form.
     */
    public function store(SaveVehicleRequest $request, CreateVehicle $create): RedirectResponse
    {
        Gate::authorize('create', Vehicle::class);

        $vehicle = $create->handle($this->actor($request), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle added.')]);

        return to_route('vehicles.show', $vehicle);
    }

    /**
     * Show a vehicle.
     */
    public function show(Request $request, Vehicle $vehicle, VehicleIndex $vehicles): Response
    {
        Gate::authorize('view', $vehicle);

        $vehicle->load(['contact:id,name', 'latestDeal']);
        $user = $this->actor($request);

        return Inertia::render('inventory/Show', [
            'vehicle' => $vehicles->present($vehicle),
            'canUpdate' => $user->can('update', $vehicle),
        ]);
    }

    /**
     * Show the form for editing a vehicle.
     */
    public function edit(Request $request, Vehicle $vehicle, VehicleIndex $vehicles): Response
    {
        Gate::authorize('update', $vehicle);

        $vehicle->load(['contact:id,name', 'latestDeal']);

        return Inertia::render('inventory/Edit', [
            'vehicle' => $vehicles->present($vehicle),
            ...$this->formOptions($this->actor($request)),
        ]);
    }

    /**
     * Update a vehicle from the inventory form.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle, UpdateVehicle $update): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        $update->handle($this->actor($request), $vehicle, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle updated.')]);

        return to_route('vehicles.show', $vehicle);
    }

    /**
     * Add a vehicle to a contact the user is allowed to manage.
     */
    public function storeForContact(StoreVehicleRequest $request, Contact $contact, CreateVehicle $create): RedirectResponse
    {
        $create->handle($this->actor($request), $request->validated(), $contact);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle added.')]);

        return to_route('contacts.edit', $contact);
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
     * @return array{contacts: list<array{id: int, name: string}>, fuels: list<array{value: string, label: string}>, transmissions: list<array{value: string, label: string}>}
     */
    private function formOptions(User $user): array
    {
        return [
            'contacts' => Contact::query()
                ->visibleTo($user)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                ])
                ->all(),
            'fuels' => $this->options(FuelType::cases()),
            'transmissions' => $this->options(Transmission::cases()),
        ];
    }

    private function prefilledContact(Request $request, User $user): ?int
    {
        $contactId = $request->integer('contact');

        if ($contactId < 1) {
            return null;
        }

        $contact = Contact::query()->find($contactId);

        if (! $contact instanceof Contact || ! $user->can('update', $contact)) {
            return null;
        }

        return $contact->id;
    }

    /**
     * @param  array<int, FuelType|Transmission|VehicleStatus>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_values(array_map(
            fn (FuelType|Transmission|VehicleStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            $cases,
        ));
    }
}
