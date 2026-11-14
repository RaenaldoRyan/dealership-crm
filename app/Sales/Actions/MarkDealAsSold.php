<?php

namespace App\Sales\Actions;

use App\Core\Actions\RecordActivity;
use App\Core\Enums\ActivityType;
use App\Core\Models\User;
use App\Inventory\Enums\VehicleStatus;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MarkDealAsSold
{
    public function __construct(private RecordActivity $activity) {}

    /**
     * Close the deal as sold and mark its vehicle sold.
     */
    public function handle(User $actor, Deal $deal): Deal
    {
        Gate::forUser($actor)->authorize('update', $deal);

        return DB::transaction(function () use ($actor, $deal): Deal {
            $deal->stage = DealStage::Sold;
            $deal->closed_at = now();
            $deal->save();

            $vehicle = $deal->vehicle;

            if ($vehicle !== null) {
                $vehicle->status = VehicleStatus::Sold;
                $vehicle->save();
            }

            $this->activity->handle($actor, $deal, ActivityType::DealSold, 'Deal marked as sold');

            return $deal;
        });
    }
}
