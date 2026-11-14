<?php

namespace Database\Factories;

use App\Core\Enums\ActivityType;
use App\Core\Models\Activity;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
#[UseModel(Activity::class)]
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => Contact::class,
            'subject_id' => Contact::factory(),
            'user_id' => User::factory(),
            'type' => ActivityType::ContactCreated,
            'description' => 'Contact created',
        ];
    }
}
