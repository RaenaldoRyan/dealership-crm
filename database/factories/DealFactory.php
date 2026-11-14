<?php

namespace Database\Factories;

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
#[UseModel(Deal::class)]
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'vehicle_id' => null,
            'user_id' => User::factory(),
            'value' => fake()->randomFloat(2, 1000, 40000),
            'stage' => DealStage::Enquiry,
        ];
    }
}
