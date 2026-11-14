<?php

namespace App\Sales\Actions;

use App\Core\Actions\RecordActivity;
use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Sales\Models\Deal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateDeal
{
    public function __construct(private RecordActivity $activity) {}

    /**
     * Open a deal owned by the acting user.
     *
     * A posted user id is ignored.
     *
     * @param  array{contact_id: int|string, vehicle_id?: int|string|null, value: int|string|float, stage: string}  $attributes
     */
    public function handle(User $actor, array $attributes): Deal
    {
        $contact = Contact::query()->findOrFail((int) $attributes['contact_id']);

        Gate::forUser($actor)->authorize('create', Deal::class);
        Gate::forUser($actor)->authorize('update', $contact);

        return DB::transaction(function () use ($actor, $contact, $attributes): Deal {
            $deal = new Deal;
            $deal->fill([
                'contact_id' => $contact->id,
                'vehicle_id' => $attributes['vehicle_id'] ?? null,
                'value' => $attributes['value'],
                'stage' => $attributes['stage'],
            ]);
            $deal->owner()->associate($actor);
            $deal->save();

            $this->activity->handle($actor, $deal, ActivityType::DealCreated, 'Deal created');

            return $deal;
        });
    }
}
