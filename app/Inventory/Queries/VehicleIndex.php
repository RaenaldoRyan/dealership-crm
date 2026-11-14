<?php

namespace App\Inventory\Queries;

use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use App\Inventory\Support\Registration;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VehicleIndex
{
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(?string $search, ?VehicleStatus $status): LengthAwarePaginator
    {
        return Vehicle::query()
            ->with(['contact:id,name', 'latestDeal'])
            ->when($status instanceof VehicleStatus, fn ($query) => $query->where('status', $status))
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $plate = '%'.Registration::normalize($search).'%';

                $query->where(function ($query) use ($like, $plate): void {
                    $query->where('make', 'like', $like)
                        ->orWhere('model', 'like', $like)
                        ->orWhere('registration', 'like', $plate)
                        ->orWhereHas('contact', fn ($query) => $query->where('name', 'like', $like));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle): array => $this->present($vehicle));
    }

    /**
     * @return array{
     *     id: int,
     *     registration: string,
     *     make: string,
     *     model: string,
     *     year: int,
     *     colour: string|null,
     *     fuelType: string|null,
     *     fuelLabel: string|null,
     *     transmission: string|null,
     *     transmissionLabel: string|null,
     *     mileage: int|null,
     *     vin: string|null,
     *     price: string,
     *     status: string,
     *     statusLabel: string,
     *     customer: string|null,
     *     contactId: int|null,
     *     deal: array{id: int, label: string}|null
     * }
     */
    public function present(Vehicle $vehicle): array
    {
        $deal = $vehicle->latestDeal;

        return [
            'id' => $vehicle->id,
            'registration' => Registration::format($vehicle->registration),
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'year' => $vehicle->year,
            'colour' => $vehicle->colour,
            'fuelType' => $vehicle->fuel_type?->value,
            'fuelLabel' => $vehicle->fuel_type?->label(),
            'transmission' => $vehicle->transmission?->value,
            'transmissionLabel' => $vehicle->transmission?->label(),
            'mileage' => $vehicle->mileage,
            'vin' => $vehicle->vin,
            'price' => $this->plainPrice($vehicle->price),
            'status' => $vehicle->status->value,
            'statusLabel' => $vehicle->status->label(),
            'customer' => $vehicle->contact?->name,
            'contactId' => $vehicle->contact_id,
            'deal' => $deal === null ? null : [
                'id' => $deal->id,
                'label' => $deal->stage->label(),
            ],
        ];
    }

    private function plainPrice(string $price): string
    {
        if (! str_contains($price, '.')) {
            return $price;
        }

        return rtrim(rtrim($price, '0'), '.');
    }
}
