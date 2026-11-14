<?php

use App\Core\Enums\ActivityType;
use App\Core\Enums\Role;
use App\Core\Models\Activity;
use App\Core\Models\Note;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Support\Facades\Hash;

test('the demo seeder creates the dealership staff and pipeline', function () {
    $this->seed();

    $manager = User::query()->where('email', 'james@dealer.co.uk')->first();
    $priya = User::query()->where('email', 'priya@dealer.co.uk')->first();
    $owen = User::query()->where('email', 'owen@dealer.co.uk')->first();
    $nia = User::query()->where('email', 'nia@dealer.co.uk')->first();

    expect($manager)->not->toBeNull()
        ->and($manager?->name)->toBe('James Whitaker')
        ->and($manager?->role)->toBe(Role::Manager)
        ->and(Hash::check('password', (string) $manager?->password))->toBeTrue()
        ->and($priya)->not->toBeNull()
        ->and($priya?->role)->toBe(Role::Rep)
        ->and($priya?->is_active)->toBeTrue()
        ->and(Hash::check('password', (string) $priya?->password))->toBeTrue()
        ->and($owen)->not->toBeNull()
        ->and($owen?->role)->toBe(Role::Rep)
        ->and($nia)->not->toBeNull()
        ->and($nia?->role)->toBe(Role::Rep)
        ->and($nia?->is_active)->toBeFalse()
        ->and(User::query()->where('role', Role::Rep)->count())->toBe(3)
        ->and($priya?->contacts()->count())->toBe(16)
        ->and($owen?->contacts()->count())->toBe(16)
        ->and($nia?->contacts()->count())->toBe(8);

    expect(Contact::query()->count())->toBe(40)
        ->and(Vehicle::query()->count())->toBe(50)
        ->and(Vehicle::query()->whereNull('contact_id')->count())->toBe(22)
        ->and(Vehicle::query()->whereNotNull('contact_id')->count())->toBe(28)
        ->and(Vehicle::query()->where('status', VehicleStatus::Reserved)->count())->toBe(4)
        ->and(Vehicle::query()->where('status', VehicleStatus::Sold)->count())->toBeGreaterThan(0)
        ->and(Deal::query()->where('stage', DealStage::Enquiry)->count())->toBe(14)
        ->and(Deal::query()->where('stage', DealStage::TestDrive)->count())->toBe(12)
        ->and(Deal::query()->where('stage', DealStage::Negotiation)->count())->toBe(10)
        ->and(Deal::query()->where('stage', DealStage::Sold)->count())->toBe(16)
        ->and(Deal::query()->where('stage', DealStage::Lost)->count())->toBe(8)
        ->and(Deal::query()->whereIn('stage', [DealStage::Sold, DealStage::Lost])->whereNull('closed_at')->count())->toBe(0)
        ->and(Deal::query()->where('stage', DealStage::Sold)->where('closed_at', '>=', now()->subDays(30))->count())->toBeGreaterThan(0)
        ->and(Deal::query()->where('stage', DealStage::Sold)->where('closed_at', '<', now()->subDays(90))->count())->toBeGreaterThan(0)
        ->and(Deal::query()->whereNotIn('stage', [DealStage::Sold, DealStage::Lost])->whereNotNull('closed_at')->count())->toBe(0)
        ->and(Note::query()->count())->toBeGreaterThan(0)
        ->and(Activity::query()->where('type', ActivityType::ContactCreated)->count())->toBe(40)
        ->and(Activity::query()->where('type', ActivityType::DealSold)->count())->toBe(16);
});
