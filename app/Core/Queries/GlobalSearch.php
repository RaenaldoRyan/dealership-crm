<?php

namespace App\Core\Queries;

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Inventory\Support\Registration;
use App\Sales\Models\Deal;

class GlobalSearch
{
    /**
     * Contacts, deals, and vehicles the viewer is allowed to open.
     *
     * @return array{
     *     contacts: list<array{id: int, label: string, detail: string, href: string}>,
     *     deals: list<array{id: int, label: string, detail: string, href: string}>,
     *     vehicles: list<array{id: int, label: string, detail: string, href: string}>
     * }
     */
    public function present(User $viewer, string $term): array
    {
        $term = trim($term);

        if ($term === '') {
            return [
                'contacts' => [],
                'deals' => [],
                'vehicles' => [],
            ];
        }

        $like = '%'.addcslashes($term, '%_\\').'%';
        $plate = '%'.Registration::normalize($term).'%';

        return [
            'contacts' => $this->contacts($viewer, $like),
            'deals' => $this->deals($viewer, $like, $plate),
            'vehicles' => $this->vehicles($plate),
        ];
    }

    /**
     * @return list<array{id: int, label: string, detail: string, href: string}>
     */
    private function contacts(User $viewer, string $like): array
    {
        return Contact::query()
            ->visibleTo($viewer)
            ->where(function ($query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email'])
            ->map(fn (Contact $contact): array => [
                'id' => $contact->id,
                'label' => $contact->name,
                'detail' => $contact->email,
                'href' => route('contacts.show', $contact),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, label: string, detail: string, href: string}>
     */
    private function deals(User $viewer, string $like, string $plate): array
    {
        return Deal::query()
            ->visibleTo($viewer)
            ->with('contact:id,name')
            ->where(function ($query) use ($like, $plate): void {
                $query->whereHas('contact', fn ($query) => $query->where('name', 'like', $like))
                    ->orWhereHas('vehicle', fn ($query) => $query->where('registration', 'like', $plate));
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Deal $deal): array => [
                'id' => $deal->id,
                'label' => $deal->contact->name,
                'detail' => $deal->stage->label(),
                'href' => route('deals.show', $deal),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, label: string, detail: string, href: string}>
     */
    private function vehicles(string $plate): array
    {
        return Vehicle::query()
            ->where('registration', 'like', $plate)
            ->orderBy('registration')
            ->limit(5)
            ->get(['id', 'registration', 'make', 'model'])
            ->map(fn (Vehicle $vehicle): array => [
                'id' => $vehicle->id,
                'label' => Registration::format($vehicle->registration),
                'detail' => $vehicle->make.' '.$vehicle->model,
                'href' => route('vehicles.show', $vehicle),
            ])
            ->all();
    }
}
