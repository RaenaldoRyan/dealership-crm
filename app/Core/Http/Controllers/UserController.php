<?php

namespace App\Core\Http\Controllers;

use App\Core\Actions\CreateUser;
use App\Core\Actions\UpdateUser;
use App\Core\Enums\Role;
use App\Core\Http\Requests\IndexUserRequest;
use App\Core\Http\Requests\StoreUserRequest;
use App\Core\Http\Requests\UpdateUserRequest;
use App\Core\Models\User;
use App\Core\Queries\UserIndex;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Show every staff account.
     */
    public function index(IndexUserRequest $request, UserIndex $users): Response
    {
        Gate::authorize('viewAny', User::class);

        $actor = $this->actor($request);

        return Inertia::render('users/Index', [
            'users' => $users->all(),
            'roles' => $this->roles(),
            'panel' => $users->panel($actor, $request->panel()),
        ]);
    }

    /**
     * Open the add-user panel on the staff list.
     */
    public function create(): RedirectResponse
    {
        Gate::authorize('create', User::class);

        return to_route('users.index', ['user' => 'new']);
    }

    /**
     * Store a verified staff account.
     */
    public function store(StoreUserRequest $request, CreateUser $create): RedirectResponse
    {
        $create->handle($this->actor($request), $request->staff());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    /**
     * Open the edit panel on the staff list.
     */
    public function edit(User $user): RedirectResponse
    {
        Gate::authorize('view', $user);

        return to_route('users.index', ['user' => $user->id]);
    }

    /**
     * Update a staff account without changing its password.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $update): RedirectResponse
    {
        $update->handle($this->actor($request), $user, $request->staff());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roles(): array
    {
        return array_map(
            fn (Role $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            Role::cases(),
        );
    }
}
