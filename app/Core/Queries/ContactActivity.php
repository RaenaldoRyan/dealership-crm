<?php

namespace App\Core\Queries;

use App\Core\Models\Activity;
use App\Core\Models\User;
use App\Core\Support\RelativeTime;
use App\Customers\Models\Contact;
use App\Sales\Models\Deal;
use Illuminate\Database\Eloquent\Builder;

class ContactActivity
{
    /**
     * The contact's activity, plus activity on deals the viewer can see.
     * Newest first.
     *
     * @return list<array{id: int, description: string, time: string}>
     */
    public function present(User $viewer, Contact $contact): array
    {
        return array_values(Activity::query()
            ->where(function (Builder $query) use ($viewer, $contact): void {
                $query->where(function (Builder $query) use ($contact): void {
                    $query->whereMorphedTo('subject', $contact);
                })->orWhere(function (Builder $query) use ($viewer, $contact): void {
                    $query->where('subject_type', (new Deal)->getMorphClass())
                        ->whereIn('subject_id', Deal::query()
                            ->whereBelongsTo($contact)
                            ->visibleTo($viewer)
                            ->select('id'));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'description' => $activity->description,
                'time' => RelativeTime::format($activity->created_at ?? now()),
            ])
            ->all());
    }
}
