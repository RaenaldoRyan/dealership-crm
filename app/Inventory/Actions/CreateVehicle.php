<?php

namespace App\Inventory\Actions;

use App\Core\Actions\RecordActivity;
use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use App\Inventory\Support\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateVehicle
{
    public function __construct(private RecordActivity $activity) {}

    /**
     * Add a stock vehicle, or attach it to the given contact.
     *
     * The contact argument wins over contact_id in the attributes, so the
     * nested contact form cannot move the vehicle onto someone else.
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
    public function handle(User $actor, array $attributes, ?Contact $contact = null): Vehicle
    {
        $assigned = $contact ?? $this->findContact($attributes['contact_id'] ?? null);

        if ($assigned instanceof Contact) {
            Gate::forUser($actor)->authorize('create', [Vehicle::class, $assigned]);
        } else {
            Gate::forUser($actor)->authorize('create', Vehicle::class);
        }

        return DB::transaction(function () use ($actor, $attributes, $assigned): Vehicle {
            $vehicle = new Vehicle;
            $this->fillDetails($vehicle, $attributes);
            $vehicle->status = VehicleStatus::InStock;
            $vehicle->contact()->associate($assigned);
            $vehicle->save();

            $this->activity->handle(
                $actor,
                $assigned ?? $vehicle,
                ActivityType::VehicleAdded,
                'Vehicle '.Registration::format($vehicle->registration).' added',
            );

            return $vehicle;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function fillDetails(Vehicle $vehicle, array $attributes): void
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
