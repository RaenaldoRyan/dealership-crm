<?php

namespace Database\Seeders;

use App\Core\Actions\AddNote;
use App\Core\Actions\CreateUser;
use App\Core\Enums\Role;
use App\Core\Models\Activity;
use App\Core\Models\Note;
use App\Core\Models\User;
use App\Customers\Actions\CreateContact;
use App\Customers\Models\Contact;
use App\Inventory\Actions\CreateVehicle;
use App\Inventory\Enums\FuelType;
use App\Inventory\Enums\Transmission;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use App\Sales\Actions\ChangeDealStage;
use App\Sales\Actions\CreateDeal;
use App\Sales\Actions\MarkDealAsSold;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the dealership demo.
     *
     * Staff share the password "password". Contacts, vehicles, and deals are
     * written through the same actions the app uses, so the activity feed is
     * real. Sold and lost deals are then given a close date in PHP.
     */
    public function run(): void
    {
        $manager = User::factory()->manager()->create([
            'name' => 'James Whitaker',
            'email' => 'james@dealer.co.uk',
            'password' => 'password',
        ]);

        $reps = $this->reps($manager);
        $contacts = $this->contacts($reps);
        $vehicles = $this->vehicles($manager, $contacts);
        $deals = $this->deals($contacts, $vehicles);

        $this->notes($contacts, $deals);
        $this->placeHistory();
    }

    /**
     * Three sales reps. The last one cannot sign in.
     *
     * @return list<User>
     */
    private function reps(User $manager): array
    {
        $create = app(CreateUser::class);

        $accounts = [
            ['name' => 'Priya Shah', 'email' => 'priya@dealer.co.uk', 'is_active' => true],
            ['name' => 'Owen Clarke', 'email' => 'owen@dealer.co.uk', 'is_active' => true],
            ['name' => 'Nia Bennett', 'email' => 'nia@dealer.co.uk', 'is_active' => false],
        ];

        return array_map(
            fn (array $account): User => $create->handle($manager, [
                'name' => $account['name'],
                'email' => $account['email'],
                'role' => Role::Rep->value,
                'is_active' => $account['is_active'],
            ]),
            $accounts,
        );
    }

    /**
     * Forty customers, split across the three reps.
     *
     * @param  list<User>  $reps
     * @return list<Contact>
     */
    private function contacts(array $reps): array
    {
        $create = app(CreateContact::class);

        $contacts = [];

        foreach ($this->names() as $index => $name) {
            $owner = $reps[$this->ownerIndex($index)];

            $contacts[] = $create->handle($owner, [
                'name' => $name,
                'email' => $this->email($name),
                'phone' => sprintf('07700 %06d', 900100 + $index),
            ]);
        }

        return $contacts;
    }

    /**
     * Twenty-two stock vehicles and twenty-eight customer vehicles.
     *
     * Four stock vehicles are reserved. Nothing in the app reserves a vehicle,
     * so the seeder sets that status after the vehicle has been added.
     *
     * @param  list<Contact>  $contacts
     * @return array<int, Vehicle>
     */
    private function vehicles(User $manager, array $contacts): array
    {
        $create = app(CreateVehicle::class);
        $byContact = [];

        for ($index = 0; $index < 22; $index++) {
            $vehicle = $create->handle($manager, $this->vehicleAttributes($index));

            if ($index < 4) {
                $vehicle->status = VehicleStatus::Reserved;
                $vehicle->save();
            }
        }

        for ($index = 0; $index < 28; $index++) {
            $contact = $contacts[(int) floor($index * count($contacts) / 28)];
            $byContact[$contact->id] = $create->handle(
                $contact->owner,
                $this->vehicleAttributes(22 + $index),
                $contact,
            );
        }

        return $byContact;
    }

    /**
     * Sixty deals across every stage. Sold deals mark their vehicle sold.
     *
     * @param  list<Contact>  $contacts
     * @param  array<int, Vehicle>  $vehicles
     * @return list<Deal>
     */
    private function deals(array $contacts, array $vehicles): array
    {
        $create = app(CreateDeal::class);
        $change = app(ChangeDealStage::class);
        $sell = app(MarkDealAsSold::class);
        $deals = [];
        $usedVehicles = [];

        foreach ($this->stages() as $index => $stage) {
            $contact = $contacts[$index % count($contacts)];
            $vehicle = $vehicles[$contact->id] ?? null;

            if (! $vehicle instanceof Vehicle || isset($usedVehicles[$vehicle->id])) {
                $vehicle = null;
            }

            $attributes = [
                'contact_id' => $contact->id,
                'value' => $this->money(8900 + (($index * 1375) % 24000)),
                'stage' => DealStage::Enquiry->value,
            ];

            if ($vehicle instanceof Vehicle) {
                $attributes['vehicle_id'] = $vehicle->id;
                $usedVehicles[$vehicle->id] = true;
            }

            $deal = $create->handle($contact->owner, $attributes);

            if ($stage === DealStage::Sold) {
                $deal = $sell->handle($contact->owner, $deal);
            } elseif ($stage !== DealStage::Enquiry) {
                $deal = $change->handle($contact->owner, $deal, $stage);
            }

            $deals[] = $this->placeInTime($deal, $index);
        }

        return $deals;
    }

    /**
     * @param  list<Contact>  $contacts
     * @param  list<Deal>  $deals
     */
    private function notes(array $contacts, array $deals): void
    {
        $add = app(AddNote::class);

        $contactNotes = [
            'Asked to be called after 6.',
            'Comparing us with a main dealer.',
            'Finance application is with the lender.',
            'Wants a part-exchange valuation.',
        ];

        foreach ($contacts as $index => $contact) {
            if ($index % 4 !== 0) {
                continue;
            }

            $add->handle($contact->owner, $contact, $contactNotes[$index % count($contactNotes)]);
        }

        $dealNotes = [
            'Booked a Saturday test drive.',
            'Holding the price until Friday.',
            'Customer asked for the service history.',
        ];

        foreach ($deals as $index => $deal) {
            if ($index % 5 !== 0) {
                continue;
            }

            $add->handle($deal->owner, $deal, $dealNotes[$index % count($dealNotes)]);
        }
    }

    /**
     * Spread notes and activity through the last few weeks.
     *
     * The actions record them at the moment of seeding. The dates are moved
     * afterwards so the contact list is not a column of "just now".
     */
    private function placeHistory(): void
    {
        Activity::query()->orderBy('id')->each(function (Activity $activity, int $index): void {
            $at = now()->subDays(($index * 2) % 21)->subHours($index % 12);
            $activity->created_at = $at;
            $activity->updated_at = $at;
            $activity->save();
        });

        Note::query()->orderBy('id')->each(function (Note $note, int $index): void {
            $at = now()->subDays(1 + (($index * 3) % 18))->subHours($index % 8);
            $note->created_at = $at;
            $note->updated_at = $at;
            $note->save();
        });
    }

    private function placeInTime(Deal $deal, int $index): Deal
    {
        $daysAgo = match ($index % 3) {
            0 => 3 + ($index % 10),
            1 => 40 + ($index % 30),
            default => 100 + ($index % 40),
        };

        $deal->created_at = now()->subDays($daysAgo + 5)->subHours($index % 12);

        if ($deal->stage === DealStage::Sold || $deal->stage === DealStage::Lost) {
            $deal->closed_at = now()->subDays($daysAgo)->subHours($index % 8);
        }

        $deal->save();

        return $deal;
    }

    /**
     * @return array{make: string, model: string, year: int, registration: string, price: string, colour: string, fuel_type: string, transmission: string, mileage: int, vin: string}
     */
    private function vehicleAttributes(int $index): array
    {
        $models = [
            ['Ford', 'Focus'],
            ['Ford', 'Fiesta'],
            ['Ford', 'Puma'],
            ['Volkswagen', 'Golf'],
            ['Volkswagen', 'Polo'],
            ['Toyota', 'Corolla'],
            ['Toyota', 'Yaris'],
            ['Toyota', 'RAV4'],
            ['Honda', 'Civic'],
            ['Honda', 'Jazz'],
            ['BMW', '3 Series'],
            ['Audi', 'A3'],
            ['Nissan', 'Qashqai'],
            ['Hyundai', 'Tucson'],
            ['Kia', 'Sportage'],
        ];
        $colours = ['White', 'Black', 'Grey', 'Blue', 'Silver', 'Red'];
        [$make, $model] = $models[$index % count($models)];

        return [
            'make' => $make,
            'model' => $model,
            'year' => 2016 + ($index % 9),
            'registration' => $this->plate($index),
            'price' => $this->money(7500 + (($index * 875) % 22000)),
            'colour' => $colours[$index % count($colours)],
            'fuel_type' => FuelType::cases()[$index % count(FuelType::cases())]->value,
            'transmission' => Transmission::cases()[$index % count(Transmission::cases())]->value,
            'mileage' => 6000 + (($index * 1370) % 80000),
            'vin' => sprintf('WF0%014d', $index + 1),
        ];
    }

    /**
     * A current-style plate, unique for each index.
     */
    private function plate(int $index): string
    {
        $letters = 'ABCDEFGHJKLMNOPRSTUVWXYZ';
        $length = strlen($letters);
        $area = $letters[intdiv($index, $length) % $length].$letters[$index % $length];
        $age = str_pad((string) (10 + ($index % 50)), 2, '0', STR_PAD_LEFT);
        $serial = '';
        $number = $index;

        for ($position = 0; $position < 3; $position++) {
            $serial .= $letters[$number % $length];
            $number = intdiv($number, $length);
        }

        return $area.$age.$serial;
    }

    /**
     * @return list<DealStage>
     */
    private function stages(): array
    {
        return [
            ...array_fill(0, 14, DealStage::Enquiry),
            ...array_fill(0, 12, DealStage::TestDrive),
            ...array_fill(0, 10, DealStage::Negotiation),
            ...array_fill(0, 16, DealStage::Sold),
            ...array_fill(0, 8, DealStage::Lost),
        ];
    }

    /**
     * Priya takes the first sixteen customers, Owen the next sixteen, Nia the last eight.
     */
    private function ownerIndex(int $index): int
    {
        return match (true) {
            $index < 16 => 0,
            $index < 32 => 1,
            default => 2,
        };
    }

    private function email(string $name): string
    {
        $slug = strtolower((string) preg_replace('/[^a-z]+/i', '.', $name));

        return trim($slug, '.').'@example.com';
    }

    private function money(int $pounds): string
    {
        return number_format($pounds, 2, '.', '');
    }

    /**
     * @return list<string>
     */
    private function names(): array
    {
        return [
            'Ada King',
            'Ben Carter',
            'Chloe Patel',
            'Daniel Brooks',
            'Emily Walsh',
            'Farah Osman',
            'George Nguyen',
            'Hannah Price',
            'Isaac Rahman',
            'Julia Evans',
            'Kyle Morris',
            'Leila Hussain',
            'Marcus Reid',
            'Nina Kowalski',
            'Oscar Diaz',
            'Poppy Bennett',
            'Quentin Shaw',
            'Rosa Alvarez',
            'Samuel Green',
            'Tara Begum',
            'Usman Ali',
            'Violet Cooper',
            'William Hughes',
            'Yasmin Khan',
            'Zara Iqbal',
            'Aaron Blake',
            'Bethany Cole',
            'Callum Doyle',
            'Daisy Fletcher',
            'Ethan Grant',
            'Freya Hughes',
            'Gabriel Idris',
            'Harriet Jones',
            'Imogen Lane',
            'Jacob Moore',
            'Katie Nash',
            "Liam O'Brien",
            'Maya Quinn',
            'Noah Stewart',
            'Olivia Turner',
        ];
    }
}
