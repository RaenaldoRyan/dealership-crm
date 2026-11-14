<?php

namespace App\Core\Actions;

use App\Core\Enums\ActivityType;
use App\Core\Models\Activity;
use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordActivity
{
    /**
     * Record what the acting user just did to a contact, deal, or vehicle.
     */
    public function handle(User $actor, Model $subject, ActivityType $type, string $description): Activity
    {
        $activity = new Activity;
        $activity->type = $type;
        $activity->description = $description;
        $activity->subject()->associate($subject);
        $activity->author()->associate($actor);
        $activity->save();

        return $activity;
    }
}
