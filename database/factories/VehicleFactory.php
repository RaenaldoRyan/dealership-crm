<?php

namespace Database\Factories;

use App\Customers\Models\Contact;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
#[UseModel(Vehicle::class)]
class VehicleFactory extends Factory
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
            'make' => fake()->randomElement(['Ford', 'Toyota', 'Volkswagen', 'Honda']),
            'model' => fake()->randomElement(['Focus', 'Corolla', 'Golf', 'Civic']),
            'year' => fake()->numberBetween(2000, (int) now()->year),
            'registration' => strtoupper(fake()->unique()->bothify('??## ???')),
            'price' => fake()->randomFloat(2, 1000, 80000),
            'status' => VehicleStatus::InStock,
        ];
    }

    /**
     * A vehicle that is still in stock, with no customer.
     */
    public function stock(): static
    {
        return $this->state(fn (): array => [
            'contact_id' => null,
        ]);
    }
}
