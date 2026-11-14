<?php

namespace Database\Seeders;

use App\Core\Models\User;
use App\Sales\Enums\DealStage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the dealership demo.
     *
     * Staff share the password "password". Contacts are split between the two
     * reps, and the open deals cover every stage.
     */
    public function run(): void
    {
        User::factory()->manager()->create([
            'name' => 'Morgan Hale',
            'email' => 'manager@crm.test',
            'password' => 'password',
        ]);

        $demo = User::factory()->rep()->create([
            'name' => 'Demo Rep',
            'email' => 'demo@crm.test',
            'password' => 'password',
        ]);

        $sam = User::factory()->rep()->create([
            'name' => 'Sam Adeyemi',
            'email' => 'sam@crm.test',
            'password' => 'password',
        ]);

        $this->seedContact($demo, [
            'name' => 'Ada King',
            'email' => 'ada.king@example.com',
            'phone' => '07700 900111',
            'make' => 'Ford',
            'model' => 'Focus',
            'year' => 2019,
            'registration' => 'AB12 CDE',
            'price' => '12500.00',
            'stage' => DealStage::Enquiry,
            'value' => '12500.00',
        ]);
        $this->seedContact($demo, [
            'name' => 'Ben Carter',
            'email' => 'ben.carter@example.com',
            'phone' => '07700 900222',
            'make' => 'Volkswagen',
            'model' => 'Golf',
            'year' => 2021,
            'registration' => 'CD34 EFG',
            'price' => '18750.00',
            'stage' => DealStage::TestDrive,
            'value' => '18000.00',
        ]);
        $this->seedContact($demo, [
            'name' => 'Chloe Patel',
            'email' => 'chloe.patel@example.com',
            'phone' => '07700 900333',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'registration' => 'EF56 HIJ',
            'price' => '16200.00',
        ]);
        $this->seedContact($demo, [
            'name' => 'Daniel Brooks',
            'email' => 'daniel.brooks@example.com',
            'phone' => '07700 900444',
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2018,
            'registration' => 'GH78 KLM',
            'price' => '11400.00',
        ]);
        $this->seedContact($demo, [
            'name' => 'Emily Walsh',
            'email' => 'emily.walsh@example.com',
            'phone' => '07700 900555',
            'make' => 'Ford',
            'model' => 'Fiesta',
            'year' => 2017,
            'registration' => 'IJ90 MNO',
            'price' => '7900.00',
        ]);

        $this->seedContact($sam, [
            'name' => 'Farah Osman',
            'email' => 'farah.osman@example.com',
            'phone' => '07700 900666',
            'make' => 'Toyota',
            'model' => 'Yaris',
            'year' => 2022,
            'registration' => 'KL12 PQR',
            'price' => '14800.00',
            'stage' => DealStage::Negotiation,
            'value' => '14500.00',
        ]);
        $this->seedContact($sam, [
            'name' => 'George Nguyen',
            'email' => 'george.nguyen@example.com',
            'phone' => '07700 900777',
            'make' => 'Volkswagen',
            'model' => 'Polo',
            'year' => 2019,
            'registration' => 'MN34 STU',
            'price' => '10250.00',
            'stage' => DealStage::Sold,
            'value' => '9800.00',
        ]);
        $this->seedContact($sam, [
            'name' => 'Hannah Price',
            'email' => 'hannah.price@example.com',
            'phone' => '07700 900888',
            'make' => 'Honda',
            'model' => 'Jazz',
            'year' => 2021,
            'registration' => 'OP56 VWX',
            'price' => '13600.00',
            'stage' => DealStage::Lost,
            'value' => '13000.00',
        ]);
        $this->seedContact($sam, [
            'name' => 'Isaac Rahman',
            'email' => 'isaac.rahman@example.com',
            'phone' => '07700 900999',
            'make' => 'Ford',
            'model' => 'Puma',
            'year' => 2023,
            'registration' => 'QR78 YZA',
            'price' => '22400.00',
        ]);
        $this->seedContact($sam, [
            'name' => 'Julia Evans',
            'email' => 'julia.evans@example.com',
            'phone' => '07700 901000',
            'make' => 'Toyota',
            'model' => 'RAV4',
            'year' => 2020,
            'registration' => 'ST90 BCD',
            'price' => '24900.00',
        ]);
    }

    /**
     * Store a contact, their vehicle, and an optional deal owned by the same rep.
     *
     * @param  array{name: string, email: string, phone: string, make: string, model: string, year: int, registration: string, price: string, stage?: DealStage, value?: string}  $record
     */
    private function seedContact(User $owner, array $record): void
    {
        $contact = $owner->contacts()->create([
            'name' => $record['name'],
            'email' => $record['email'],
            'phone' => $record['phone'],
        ]);

        $vehicle = $contact->vehicles()->create([
            'make' => $record['make'],
            'model' => $record['model'],
            'year' => $record['year'],
            'registration' => $record['registration'],
            'price' => $record['price'],
        ]);

        $stage = $record['stage'] ?? null;
        $value = $record['value'] ?? null;

        if (! $stage instanceof DealStage || $value === null) {
            return;
        }

        $owner->deals()->create([
            'contact_id' => $contact->id,
            'vehicle_id' => $vehicle->id,
            'value' => $value,
            'stage' => $stage,
        ]);
    }
}
