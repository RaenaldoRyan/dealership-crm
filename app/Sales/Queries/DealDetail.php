<?php

namespace App\Sales\Queries;

use App\Core\Queries\NotableNotes;
use App\Inventory\Support\Registration;
use App\Sales\Models\Deal;

class DealDetail
{
    public function __construct(private NotableNotes $notes) {}

    /**
     * @return array{
     *     id: int,
     *     value: string,
     *     stage: string,
     *     stageLabel: string,
     *     contact: array{id: int, name: string, email: string, phone: string|null, owner: array{name: string}},
     *     vehicle: array{id: int, make: string, model: string, year: int, registration: string, price: string}|null,
     *     notes: list<array{id: int, body: string, author: string, time: string}>
     * }
     */
    public function present(Deal $deal): array
    {
        $deal->load([
            'contact.owner:id,name',
            'vehicle',
        ]);

        $contact = $deal->contact;
        $vehicle = $deal->vehicle;

        return [
            'id' => $deal->id,
            'value' => $deal->value,
            'stage' => $deal->stage->value,
            'stageLabel' => $deal->stage->label(),
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'owner' => [
                    'name' => $contact->owner->name,
                ],
            ],
            'vehicle' => $vehicle === null ? null : [
                'id' => $vehicle->id,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'registration' => Registration::format($vehicle->registration),
                'price' => $vehicle->price,
            ],
            'notes' => $this->notes->present($deal),
        ];
    }
}
