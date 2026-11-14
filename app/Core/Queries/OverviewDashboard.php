<?php

namespace App\Core\Queries;

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use App\Sales\Queries\PipelineStats;
use Illuminate\Database\Eloquent\Builder;

class OverviewDashboard
{
    public function __construct(private PipelineStats $stats) {}

    /**
     * Pipeline, inventory, and recent deals for the signed-in user.
     *
     * A manager may narrow the page to one rep. That choice is ignored for a rep.
     *
     * @return array{
     *     canFilterReps: bool,
     *     reps: list<array{id: int, name: string}>,
     *     selectedRep: int|null,
     *     metrics: array{pipelineValue: string, openDeals: int, conversion: int, averageDaysToClose: int},
     *     stages: list<array{value: string, label: string, count: int}>,
     *     inventory: array{inStock: int, linkedToDeals: int, averagePrice: string},
     *     recentDeals: list<array{id: int, contact: string, vehicle: string|null, value: string, stage: string, stageLabel: string}>
     * }
     */
    public function present(User $user, ?int $repId): array
    {
        $repId = $user->role === Role::Manager ? $repId : null;
        $deals = $this->deals($user, $repId);
        $vehicles = $this->vehicles($user, $repId);

        $stageCounts = (clone $deals)
            ->selectRaw('stage, count(*) as aggregate')
            ->groupBy('stage')
            ->pluck('aggregate', 'stage');

        return [
            'canFilterReps' => $user->role === Role::Manager,
            'reps' => $user->role === Role::Manager
                ? array_values(User::query()
                    ->where('role', Role::Rep)
                    ->orderBy('name')
                    ->orderBy('id')
                    ->get(['id', 'name'])
                    ->map(fn (User $rep): array => [
                        'id' => $rep->id,
                        'name' => $rep->name,
                    ])
                    ->all())
                : [],
            'selectedRep' => $repId,
            'metrics' => $this->stats->present($user, $repId),
            'stages' => array_map(
                fn (DealStage $stage): array => [
                    'value' => $stage->value,
                    'label' => $stage->label(),
                    'count' => (int) ($stageCounts[$stage->value] ?? 0),
                ],
                DealStage::cases(),
            ),
            'inventory' => [
                'inStock' => (clone $vehicles)
                    ->whereDoesntHave('deals', fn (Builder $query) => $query->where('stage', DealStage::Sold->value))
                    ->count(),
                'linkedToDeals' => (clone $vehicles)->whereHas('deals')->count(),
                'averagePrice' => number_format((float) ((clone $vehicles)->avg('price') ?? 0), 2, '.', ''),
            ],
            'recentDeals' => array_values((clone $deals)
                ->with(['contact:id,name', 'vehicle:id,make,model'])
                ->latest()
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (Deal $deal): array => [
                    'id' => $deal->id,
                    'contact' => $deal->contact->name,
                    'vehicle' => $deal->vehicle === null
                        ? null
                        : $deal->vehicle->make.' '.$deal->vehicle->model,
                    'value' => $deal->value,
                    'stage' => $deal->stage->value,
                    'stageLabel' => $deal->stage->label(),
                ])
                ->all()),
        ];
    }

    /**
     * Deals the user may see, narrowed to one rep when a manager asks.
     *
     * @return Builder<Deal>
     */
    private function deals(User $user, ?int $repId): Builder
    {
        $query = Deal::query()->visibleTo($user);

        if ($repId !== null) {
            $query->where('user_id', $repId);
        }

        return $query;
    }

    /**
     * Vehicles on contacts the user may see, narrowed the same way.
     *
     * @return Builder<Vehicle>
     */
    private function vehicles(User $user, ?int $repId): Builder
    {
        return Vehicle::query()->whereHas(
            'contact',
            function (Builder $query) use ($user, $repId): void {
                /** @var Builder<Contact> $query */
                $query->visibleTo($user);

                if ($repId !== null) {
                    $query->where('user_id', $repId);
                }
            },
        );
    }
}
