<?php

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Support\Facades\Hash;

test('the demo seeder creates the dealership staff and pipeline', function () {
    $this->seed();

    $manager = User::query()->where('email', 'manager@crm.test')->first();
    $demo = User::query()->where('email', 'demo@crm.test')->first();
    $sam = User::query()->where('email', 'sam@crm.test')->first();

    expect($manager)->not->toBeNull()
        ->and($manager?->role)->toBe(Role::Manager)
        ->and(Hash::check('password', (string) $manager?->password))->toBeTrue()
        ->and($demo)->not->toBeNull()
        ->and($demo?->role)->toBe(Role::Rep)
        ->and(Hash::check('password', (string) $demo?->password))->toBeTrue()
        ->and($sam)->not->toBeNull()
        ->and($sam?->role)->toBe(Role::Rep)
        ->and(Hash::check('password', (string) $sam?->password))->toBeTrue()
        ->and(User::query()->where('role', Role::Rep)->count())->toBe(2);

    expect(Contact::query()->count())->toBe(10)
        ->and(Vehicle::query()->count())->toBe(10)
        ->and(Contact::query()->has('vehicles')->count())->toBe(10)
        ->and($demo?->contacts()->count())->toBe(5)
        ->and($sam?->contacts()->count())->toBe(5);

    $stages = Deal::query()
        ->pluck('stage')
        ->map(fn (DealStage $stage): string => $stage->value)
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($stages)->toBe([
        'enquiry',
        'lost',
        'negotiation',
        'sold',
        'test_drive',
    ]);
});
