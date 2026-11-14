<?php

namespace App\Sales\Queries;

use App\Core\Models\User;
use App\Sales\Models\Deal;

class PipelineBoard
{
    /**
     * Deals on the board, newest first.
     *
     * @return list<array{id: int, value: string, stage: string, contact: array{name: string}, vehicle: array{name: string}|null, owner: array{name: string}}>
     */
    public function present(User $user): array
    {
        return Deal::query()
            ->visibleTo($user)
            ->with([
                'contact:id,name',
                'vehicle:id,make,model',
                'owner:id,name',
            ])
            ->latest()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Deal $deal): array => [
                'id' => $deal->id,
                'value' => $deal->value,
                'stage' => $deal->stage->value,
                'contact' => [
                    'name' => $deal->contact->name,
                ],
                'vehicle' => $deal->vehicle === null ? null : [
                    'name' => $deal->vehicle->make.' '.$deal->vehicle->model,
                ],
                'owner' => [
                    'name' => $deal->owner->name,
                ],
            ])
            ->all();
    }
}
