<?php

namespace Database\Factories;

use App\Core\Models\Note;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
#[UseModel(Note::class)]
class NoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notable_type' => Contact::class,
            'notable_id' => Contact::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
