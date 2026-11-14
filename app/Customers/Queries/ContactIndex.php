<?php

namespace App\Customers\Queries;

use App\Core\Models\Activity;
use App\Core\Models\User;
use App\Core\Support\RelativeTime;
use App\Customers\Models\Contact;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ContactIndex
{
    /**
     * Contacts the viewer may see, eight per page.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(User $viewer, ?string $search, string $filter): LengthAwarePaginator
    {
        return Contact::query()
            ->visibleTo($viewer)
            ->with('owner:id,name')
            ->withCount([
                'vehicles',
                'deals as open_deals_count' => fn (Builder $query) => $this->openDeals($query, $viewer),
            ])
            ->addSelect([
                'last_activity_at' => $this->lastActivity($viewer),
            ])
            ->when($filter === 'mine', fn (Builder $query) => $query->whereBelongsTo($viewer, 'owner'))
            ->when($filter === 'open', fn (Builder $query) => $query->whereHas(
                'deals',
                fn (Builder $query) => $this->openDeals($query, $viewer),
            ))
            ->when($search !== null, function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function (Builder $query) use ($like): void {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->latest('id')
            ->paginate(8)
            ->withQueryString()
            ->through(fn (Contact $contact): array => $this->present($contact));
    }

    /**
     * @param  Builder<Deal>  $query
     */
    private function openDeals(Builder $query, User $viewer): void
    {
        $query->visibleTo($viewer)
            ->whereNotIn('stage', [DealStage::Sold, DealStage::Lost]);
    }

    /**
     * The newest activity on the contact, or on a deal the viewer can see.
     *
     * @return Builder<Activity>
     */
    private function lastActivity(User $viewer): Builder
    {
        return Activity::query()
            ->select('created_at')
            ->where(function (Builder $query) use ($viewer): void {
                $query->where(function (Builder $query): void {
                    $query->where('subject_type', (new Contact)->getMorphClass())
                        ->whereColumn('subject_id', 'contacts.id');
                })->orWhere(function (Builder $query) use ($viewer): void {
                    $query->where('subject_type', (new Deal)->getMorphClass())
                        ->whereIn('subject_id', Deal::query()
                            ->select('id')
                            ->whereColumn('deals.contact_id', 'contacts.id')
                            ->visibleTo($viewer));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: string|null,
     *     vehicles: int,
     *     openDeals: int,
     *     owner: string,
     *     lastActivity: string|null
     * }
     */
    private function present(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'vehicles' => (int) $contact->vehicles_count,
            'openDeals' => (int) $contact->open_deals_count,
            'owner' => $this->initials($contact->owner->name),
            'lastActivity' => $this->activityTime($contact->getAttribute('last_activity_at')),
        ];
    }

    private function activityTime(mixed $at): ?string
    {
        if ($at instanceof CarbonInterface) {
            return RelativeTime::format($at);
        }

        if (! is_string($at) || $at === '') {
            return null;
        }

        return RelativeTime::format(Carbon::parse($at));
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return collect($parts)
            ->filter(fn (string $part): bool => $part !== '')
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
