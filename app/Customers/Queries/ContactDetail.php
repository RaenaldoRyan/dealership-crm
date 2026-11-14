<?php

namespace App\Customers\Queries;

use App\Core\Models\User;
use App\Core\Queries\ContactActivity;
use App\Core\Queries\NotableNotes;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Inventory\Support\Registration;
use App\Sales\Models\Deal;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactDetail
{
    public function __construct(
        private NotableNotes $notes,
        private ContactActivity $activity,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: string|null,
     *     owner: array{name: string},
     *     vehicles: list<array{id: int, year: int, make: string, model: string, registration: string, price: string}>,
     *     deals: list<array{id: int, stage: string, stageLabel: string, vehicle: string|null, value: string}>,
     *     notes: list<array{id: int, body: string, author: string, time: string}>,
     *     activity: list<array{id: int, description: string, time: string}>
     * }
     */
    public function present(User $viewer, Contact $contact): array
    {
        $contact->load([
            'owner:id,name',
            'vehicles' => fn (HasMany $query) => $query->latest('id'),
            'deals' => function (HasMany $query) use ($viewer): void {
                $query->visibleTo($viewer)
                    ->with('vehicle:id,make,model')
                    ->latest('id');
            },
        ]);

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'owner' => [
                'name' => $contact->owner->name,
            ],
            'vehicles' => $contact->vehicles
                ->map(fn (Vehicle $vehicle): array => [
                    'id' => $vehicle->id,
                    'year' => $vehicle->year,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'registration' => Registration::format($vehicle->registration),
                    'price' => $vehicle->price,
                ])
                ->all(),
            'deals' => $contact->deals
                ->map(fn (Deal $deal): array => [
                    'id' => $deal->id,
                    'stage' => $deal->stage->value,
                    'stageLabel' => $deal->stage->label(),
                    'vehicle' => $deal->vehicle === null
                        ? null
                        : $deal->vehicle->make.' '.$deal->vehicle->model,
                    'value' => $deal->value,
                ])
                ->all(),
            'notes' => $this->notes->present($contact),
            'activity' => $this->activity->present($viewer, $contact),
        ];
    }
}
