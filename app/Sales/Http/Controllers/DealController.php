<?php

namespace App\Sales\Http\Controllers;

use App\Core\Actions\AddNote;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Http\Controllers\Controller;
use App\Inventory\Models\Vehicle;
use App\Sales\Actions\ChangeDealStage;
use App\Sales\Actions\CreateDeal;
use App\Sales\Actions\MarkDealAsSold;
use App\Sales\Enums\DealStage;
use App\Sales\Http\Requests\SoldDealRequest;
use App\Sales\Http\Requests\StoreDealNoteRequest;
use App\Sales\Http\Requests\StoreDealRequest;
use App\Sales\Http\Requests\UpdateDealRequest;
use App\Sales\Models\Deal;
use App\Sales\Queries\DealDetail;
use App\Sales\Queries\PipelineBoard;
use App\Sales\Queries\PipelineStats;
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
    public function index(Request $request, PipelineBoard $board, PipelineStats $stats): Response
    {
        Gate::authorize('viewAny', Deal::class);

        $user = $this->actor($request);

        return Inertia::render('sales/Index', [
            'deals' => $board->present($user),
            'stages' => $this->stages(),
            'metrics' => $stats->present($user),
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

        $requested = $request->integer('contact');
        $contactId = collect($contacts)->contains('id', $requested) ? $requested : null;

        return Inertia::render('sales/Create', [
            'contacts' => $contacts,
            'stages' => $this->stages(),
            'contactId' => $contactId,
        ]);
    }

    /**
     * Store a deal owned by the current user.
     */
    public function store(StoreDealRequest $request, CreateDeal $create): RedirectResponse
    {
        Gate::authorize('create', Deal::class);

        $create->handle($this->actor($request), $request->deal());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal created.')]);

        return to_route('deals.index');
    }

    /**
     * Show a deal.
     */
    public function show(Deal $deal, DealDetail $detail): Response
    {
        Gate::authorize('view', $deal);

        return Inertia::render('sales/Show', [
            'deal' => $detail->present($deal),
            'stages' => $this->stages(),
        ]);
    }

    /**
     * Update the stage of a deal the user is allowed to manage.
     */
    public function update(UpdateDealRequest $request, Deal $deal, ChangeDealStage $change): RedirectResponse
    {
        Gate::authorize('update', $deal);

        $change->handle(
            $this->actor($request),
            $deal,
            DealStage::from((string) $request->validated('stage')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal stage updated.')]);

        return redirect()->back(fallback: route('deals.index'));
    }

    /**
     * Mark a deal, and its vehicle, as sold.
     */
    public function sold(SoldDealRequest $request, Deal $deal, MarkDealAsSold $mark): RedirectResponse
    {
        Gate::authorize('update', $deal);

        $mark->handle($this->actor($request), $deal);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal marked as sold.')]);

        return to_route('deals.show', $deal);
    }

    /**
     * Add a note to a deal.
     */
    public function storeNote(StoreDealNoteRequest $request, Deal $deal, AddNote $add): RedirectResponse
    {
        Gate::authorize('update', $deal);

        $add->handle($this->actor($request), $deal, (string) $request->validated('body'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Note added.')]);

        return to_route('deals.show', $deal);
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
