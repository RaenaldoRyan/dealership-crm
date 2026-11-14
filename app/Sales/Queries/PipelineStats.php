<?php

namespace App\Sales\Queries;

use App\Core\Models\User;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Database\Eloquent\Builder;

class PipelineStats
{
    /**
     * Pipeline figures for the deals a user can see.
     *
     * Days to close are measured in PHP so the result does not depend on
     * the database's date functions.
     *
     * @return array{pipelineValue: string, openDeals: int, conversion: int, averageDaysToClose: int}
     */
    public function present(User $user, ?int $repId = null): array
    {
        $deals = $this->deals($user, $repId);

        $sold = (clone $deals)->where('stage', DealStage::Sold->value)->count();
        $lost = (clone $deals)->where('stage', DealStage::Lost->value)->count();
        $closed = $sold + $lost;

        $durations = (clone $deals)
            ->where('stage', DealStage::Sold->value)
            ->whereNotNull('closed_at')
            ->get(['created_at', 'closed_at']);

        $averageDays = $durations->isEmpty()
            ? 0
            : (int) round($durations->avg(
                fn (Deal $deal): float => $deal->created_at?->diffInDays($deal->closed_at, true) ?? 0,
            ));

        return [
            'pipelineValue' => number_format((float) (clone $deals)
                ->whereNotIn('stage', [DealStage::Sold->value, DealStage::Lost->value])
                ->sum('value'), 2, '.', ''),
            'openDeals' => (clone $deals)
                ->whereNotIn('stage', [DealStage::Sold->value, DealStage::Lost->value])
                ->count(),
            'conversion' => $closed === 0 ? 0 : (int) round(($sold / $closed) * 100),
            'averageDaysToClose' => $averageDays,
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
}
