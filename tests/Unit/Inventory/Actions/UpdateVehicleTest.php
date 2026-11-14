<?php

use App\Core\Enums\ActivityType;
use App\Core\Models\Activity;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Actions\UpdateVehicle;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use Illuminate\Auth\Access\AuthorizationException;

it('updates the details and leaves the status unchanged', function () {
    $rep = User::factory()->rep()->create();
    $vehicle = Vehicle::factory()->stock()->create([
        'status' => VehicleStatus::Reserved,
        'registration' => 'AB22 CDE',
        'model' => 'Focus',
    ]);

    $updated = app(UpdateVehicle::class)->handle($rep, $vehicle, [
        'make' => 'Ford',
        'model' => 'Puma',
        'year' => 2022,
        'registration' => 'AB22 CDE',
        'price' => '18000',
        'contact_id' => null,
        'status' => 'sold',
    ]);

    expect($updated->model)->toBe('Puma')
        ->and($updated->status)->toBe(VehicleStatus::Reserved)
        ->and($updated->contact_id)->toBeNull()
        ->and($updated->registration)->toBe('AB22CDE');

    expect(Activity::query()->count())->toBe(0);
});

it('lets a rep assign their own contact to a stock vehicle', function () {
    $rep = User::factory()->rep()->create();
    $contact = Contact::factory()->for($rep, 'owner')->create();
    $vehicle = Vehicle::factory()->stock()->create(['registration' => 'AB22 CDE']);

    $updated = app(UpdateVehicle::class)->handle($rep, $vehicle, [
        'make' => $vehicle->make,
        'model' => $vehicle->model,
        'year' => $vehicle->year,
        'registration' => $vehicle->registration,
        'price' => $vehicle->price,
        'contact_id' => $contact->id,
    ]);

    expect($updated->contact_id)->toBe($contact->id);

    $this->assertDatabaseHas('activities', [
        'subject_type' => Contact::class,
        'subject_id' => $contact->id,
        'user_id' => $rep->id,
        'type' => ActivityType::VehicleLinked->value,
        'description' => 'Vehicle AB22 CDE linked',
    ]);
});

it('forbids a rep from editing another reps customer vehicle', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();
    $vehicle = Vehicle::factory()->for($contact)->create();

    expect(fn () => app(UpdateVehicle::class)->handle($rep, $vehicle, [
        'make' => 'Ford',
        'model' => 'Puma',
        'year' => 2022,
        'registration' => 'AB22 CDE',
        'price' => '1',
    ]))->toThrow(AuthorizationException::class);
});

it('forbids a rep from assigning another reps contact', function () {
    $rep = User::factory()->rep()->create();
    $other = User::factory()->rep()->create();
    $contact = Contact::factory()->for($other, 'owner')->create();
    $vehicle = Vehicle::factory()->stock()->create(['registration' => 'AB22 CDE']);

    expect(fn () => app(UpdateVehicle::class)->handle($rep, $vehicle, [
        'make' => $vehicle->make,
        'model' => $vehicle->model,
        'year' => $vehicle->year,
        'registration' => $vehicle->registration,
        'price' => $vehicle->price,
        'contact_id' => $contact->id,
    ]))->toThrow(AuthorizationException::class);

    expect($vehicle->refresh()->contact_id)->toBeNull();
});
