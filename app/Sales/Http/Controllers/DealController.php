<?php

namespace App\Sales\Http\Controllers;

use App\Customers\Models\Contact;
use App\Http\Controllers\Controller;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Http\Requests\StoreDealRequest;
use App\Sales\Http\Requests\UpdateDealRequest;
use App\Sales\Models\Deal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    /**
     * Show the deals the current user is allowed to see.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Deal::class);

        $deals = Deal::query()
            ->visibleTo($request->user())
            ->with([
                'contact:id,name',
                'vehicle:id,make,model',
                'owner:id,name',
            ])
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Deal $deal): array => $this->present($deal))
            ->all();

        return Inertia::render('sales/Index', [
            'deals' => $deals,
            'stages' => $this->stages(),
        ]);
    }

    /**
     * Show the form for creating a deal.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Deal::class);

        $contacts = Contact::query()
            ->visibleTo($request->user())
            ->with('vehicles:id,contact_id,make,model,registration')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Contact $contact): array => [
                'id' => $contact->id,
                'name' => $contact->name,
                'vehicles' => $contact->vehicles
                    ->map(fn (Vehicle $vehicle): array => [
                        'id' => $vehicle->id,
                        'label' => $vehicle->make.' '.$vehicle->model.' ('.$vehicle->registration.')',
                    ])
                    ->all(),
            ])
            ->all();

        return Inertia::render('sales/Create', [
            'contacts' => $contacts,
            'stages' => $this->stages(),
        ]);
    }

    /**
     * Store a deal owned by the current user.
     */
    public function store(StoreDealRequest $request): RedirectResponse
    {
        $request->user()->deals()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal created.')]);

        return to_route('deals.index');
    }

    /**
     * Update the stage of a deal the user is allowed to manage.
     */
    public function update(UpdateDealRequest $request, Deal $deal): RedirectResponse
    {
        $deal->update($request->safe()->only(['stage']));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal stage updated.')]);

        return to_route('deals.index');
    }

    /**
     * @return array{id: int, value: string, stage: string, contact: array{name: string}, vehicle: array{name: string}|null, owner: array{name: string}}
     */
    private function present(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'value' => $deal->value,
            'stage' => $deal->stage->value,
            'contact' => [
                'name' => $deal->contact->name,
            ],
            'vehicle' => $deal->vehicle === null ? null : [
                'name' => $deal->vehicle->make.' '.$deal->vehicle->model,
            ],
            'owner' => [
                'name' => $deal->owner->name,
            ],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function stages(): array
    {
        return array_map(
            fn (DealStage $stage): array => [
                'value' => $stage->value,
                'label' => $stage->label(),
            ],
            DealStage::cases(),
        );
    }
}
