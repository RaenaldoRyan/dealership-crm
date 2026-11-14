<?php

namespace App\Inventory\Actions;

use App\Core\Actions\RecordActivity;
use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Inventory\Support\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateVehicle
{
    public function __construct(private RecordActivity $activity) {}

    /**
     * Update the vehicle details. Status is left unchanged.
     *
     * @param  array{
     *     make: string,
     *     model: string,
     *     year: int|string,
     *     registration: string,
     *     price: int|string|float,
     *     colour?: string|null,
     *     fuel_type?: string|null,
     *     transmission?: string|null,
     *     mileage?: int|string|null,
     *     vin?: string|null,
     *     contact_id?: int|string|null
     * }  $attributes
     */
    public function handle(User $actor, Vehicle $vehicle, array $attributes): Vehicle
    {
        Gate::forUser($actor)->authorize('update', $vehicle);

        $previousContactId = $vehicle->contact_id;
        $assigned = $vehicle->contact;

        if (array_key_exists('contact_id', $attributes)) {
            $assigned = $this->findContact($attributes['contact_id']);
        }

        if ($assigned instanceof Contact) {
            Gate::forUser($actor)->authorize('update', $assigned);
        }

        return DB::transaction(function () use ($actor, $vehicle, $attributes, $assigned, $previousContactId): Vehicle {
            $this->fill($vehicle, $attributes);
            $vehicle->contact()->associate($assigned);
            $vehicle->save();

            $previous = $previousContactId === null ? null : (int) $previousContactId;

            if ($assigned instanceof Contact && $assigned->id !== $previous) {
                $this->activity->handle(
                    $actor,
                    $assigned,
                    ActivityType::VehicleLinked,
                    'Vehicle '.Registration::format($vehicle->registration).' linked',
                );
            }

            return $vehicle;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fill(Vehicle $vehicle, array $attributes): void
    {
        $vehicle->fill([
            'make' => $attributes['make'],
            'model' => $attributes['model'],
            'year' => $attributes['year'],
            'registration' => $attributes['registration'],
            'price' => $attributes['price'],
            'colour' => $attributes['colour'] ?? null,
            'fuel_type' => $attributes['fuel_type'] ?? null,
            'transmission' => $attributes['transmission'] ?? null,
            'mileage' => $attributes['mileage'] ?? null,
            'vin' => $attributes['vin'] ?? null,
        ]);
    }

    private function findContact(mixed $contactId): ?Contact
    {
        if ($contactId === null || $contactId === '') {
            return null;
        }

        return Contact::query()->findOrFail((int) $contactId);
    }
}
