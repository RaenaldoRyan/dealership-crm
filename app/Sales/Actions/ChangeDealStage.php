<?php

namespace App\Sales\Actions;

use App\Core\Actions\RecordActivity;
use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChangeDealStage
{
    public function __construct(private RecordActivity $activity) {}

    /**
     * Move a deal to another stage.
     *
     * Sold and lost record a close time when the deal does not have one.
     * An open stage clears it. The vehicle status is left unchanged.
     */
    public function handle(User $actor, Deal $deal, DealStage $stage): Deal
    {
        Gate::forUser($actor)->authorize('update', $deal);

        $from = $deal->stage;

        return DB::transaction(function () use ($actor, $deal, $stage, $from): Deal {
            $deal->stage = $stage;

            if ($stage === DealStage::Sold || $stage === DealStage::Lost) {
                $deal->closed_at ??= now();
            } else {
                $deal->closed_at = null;
            }

            $deal->save();

            if ($from !== $stage) {
                $this->activity->handle(
                    $actor,
                    $deal,
                    ActivityType::StageChanged,
                    'Stage changed from '.$from->label().' to '.$stage->label(),
                );
            }

            return $deal;
        });
    }
}
