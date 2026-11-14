<?php

use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Actions\CreateVehicle;
use App\Inventory\Enums\FuelType;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;

it('stores a stock vehicle with a normalised registration', function () {
    $rep = User::factory()->rep()->create();

    $vehicle = app(CreateVehicle::class)->handle($rep, [
        'make' => 'Ford',
        'model' => 'Puma',
        'year' => 2022,
        'registration' => 'AB22 CDE',
        'price' => '18950',
        'colour' => 'Agate Black',
        'fuel_type' => 'petrol',
        'transmission' => 'manual',
        'mileage' => 18400,
    ]);

    expect($vehicle->contact_id)->toBeNull()
        ->and($vehicle->registration)->toBe('AB22CDE')
        ->and($vehicle->status)->toBe(VehicleStatus::InStock)
        ->and($vehicle->fuel_type)->toBe(FuelType::Petrol)
        ->and($vehicle->mileage)->toBe(18400);

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'registration' => 'AB22CDE',
        'status' => VehicleStatus::InStock->value,
        'contact_id' => null,
    ]);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Vehicle::class,
        'subject_id' => $vehicle->id,
        'user_id' => $rep->id,
        'type' => ActivityType::VehicleAdded->value,
        'description' => 'Vehicle AB22 CDE added',
    ]);
});

it('attaches the given contact and ignores a posted contact id', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $other = Contact::factory()->for($rep, 'owner')->create();

    $vehicle = app(CreateVehicle::class)->handle($rep, [
        'make' => 'Ford',
        'model' => 'Focus',
        'year' => 2020,
        'registration' => 'AB12 CDE',
        'price' => '12500.50',
        'contact_id' => $other->id,
    ], $contact);

    expect($vehicle->contact_id)->toBe($contact->id);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Contact::class,
        'subject_id' => $contact->id,
        'type' => ActivityType::VehicleAdded->value,
        'description' => 'Vehicle AB12 CDE added',
    ]);
});

it('forbids a rep from adding a vehicle to another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();

    expect(fn () => app(CreateVehicle::class)->handle($rep, [
        'make' => 'Ford',
        'model' => 'Focus',
        'year' => 2020,
        'registration' => 'AB12 CDE',
        'price' => '1',
    ], $contact))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('vehicles', 0);
});
