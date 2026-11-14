<?php

namespace App\Core\Http\Controllers;

use App\Core\Http\Requests\SearchRequest;
use App\Core\Models\User;
use App\Core\Queries\GlobalSearch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * Search the contacts, deals, and vehicles the current user can open.
     */
    public function __invoke(SearchRequest $request, GlobalSearch $search): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return response()->json($search->present($user, $request->term()));
    }
}
