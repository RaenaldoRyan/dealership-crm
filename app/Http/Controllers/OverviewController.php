<?php

namespace App\Http\Controllers;

use App\Core\Models\User;
use App\Core\Queries\OverviewDashboard;
use App\Http\Requests\OverviewRequest;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    /**
     * Show pipeline, inventory, and recent deals for the signed-in user.
     */
    public function __invoke(OverviewRequest $request, OverviewDashboard $overview): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return Inertia::render('overview/Index', $overview->present($user, $request->repId()));
    }
}
