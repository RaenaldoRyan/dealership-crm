<?php

namespace App\Sales\Queries;

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SalesReport
{
    /**
     * Sales, conversion, and stage counts for a period.
     *
     * Sold and lost deals fall in the period by their close date. Open deals
     * fall in it by the day they were created. The comparison is done in PHP
     * so it does not depend on the database's date functions.
     *
     * @return array{
     *     reps: list<array{id: int, name: string, sold: int, value: string, conversion: int}>,
     *     stages: list<array{value: string, label: string, count: int}>
     * }
     */
    public function present(string $range): array
    {
        $deals = $this->inPeriod($range);

        return [
            'reps' => $this->reps($deals),
            'stages' => $this->stages($deals),
        ];
    }

    /**
     * The sales table as a CSV download.
     */
    public function csv(string $range): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, ['Rep', 'Sold', 'Value', 'Conversion']);

        foreach ($this->present($range)['reps'] as $rep) {
            fputcsv($handle, [
                $this->cell($rep['name']),
                $rep['sold'],
                $rep['value'],
                $rep['conversion'].'%',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    /**
     * @return Collection<int, Deal>
     */
    private function inPeriod(string $range): Collection
    {
        $since = $this->since($range);

        return Deal::query()
            ->get(['id', 'user_id', 'stage', 'value', 'created_at', 'closed_at'])
            ->filter(function (Deal $deal) use ($since): bool {
                if ($since === null) {
                    return true;
                }

                $at = $deal->stage === DealStage::Sold || $deal->stage === DealStage::Lost
                    ? $deal->closed_at
                    : $deal->created_at;

                return $at instanceof CarbonInterface && $at->greaterThanOrEqualTo($since);
            })
            ->values();
    }

    private function since(string $range): ?CarbonInterface
    {
        return match ($range) {
            'all' => null,
            '90' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }

    /**
     * @param  Collection<int, Deal>  $deals
     * @return list<array{id: int, name: string, sold: int, value: string, conversion: int}>
     */
    private function reps(Collection $deals): array
    {
        $ids = User::query()
            ->where('role', Role::Rep)
            ->pluck('id')
            ->merge($deals->pluck('user_id'))
            ->unique()
            ->all();

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(function (User $user) use ($deals): array {
                $owned = $deals->where('user_id', $user->id);
                $sold = $owned->where('stage', DealStage::Sold);
                $lost = $owned->where('stage', DealStage::Lost)->count();
                $soldCount = $sold->count();
                $closed = $soldCount + $lost;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'sold' => $soldCount,
                    'value' => number_format((float) $sold->sum(
                        fn (Deal $deal): float => (float) $deal->value,
                    ), 2, '.', ''),
                    'conversion' => $closed === 0 ? 0 : (int) round(($soldCount / $closed) * 100),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, Deal>  $deals
     * @return list<array{value: string, label: string, count: int}>
     */
    private function stages(Collection $deals): array
    {
        return array_map(
            fn (DealStage $stage): array => [
                'value' => $stage->value,
                'label' => $stage->label(),
                'count' => $deals->where('stage', $stage)->count(),
            ],
            DealStage::cases(),
        );
    }

    /**
     * Stop a spreadsheet treating a name as a formula.
     */
    private function cell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
