<?php

namespace App\Core\Http\Controllers;

use App\Core\Enums\Role;
use App\Core\Http\Requests\StoreUserRequest;
use App\Core\Http\Requests\UpdateUserRequest;
use App\Core\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * The password given to a staff account a manager creates.
     */
    private const string InitialPassword = 'password';

    /**
     * Show every staff account.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user): array => $this->present($user))
            ->all();

        return Inertia::render('users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a staff account.
     */
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('users/Create', [
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Store a verified staff account.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = new User;
        $user->name = $request->string('name')->toString();
        $user->email = $request->string('email')->toString();
        $user->password = self::InitialPassword;
        $user->role = Role::from($request->string('role')->toString());
        $user->email_verified_at = now();
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    /**
     * Show the form for editing a staff account.
     */
    public function edit(User $user): Response
    {
        Gate::authorize('view', $user);

        return Inertia::render('users/Edit', [
            'user' => $this->present($user),
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Update a staff account without changing its password.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->name = $request->string('name')->toString();
        $user->email = $request->string('email')->toString();
        $user->role = Role::from($request->string('role')->toString());
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    /**
     * Remove a staff account the current user is allowed to delete.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User removed.')]);

        return to_route('users.index');
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];
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
