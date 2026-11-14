<?php

namespace App\Core\Actions;

use App\Core\Enums\Role;
use App\Core\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateUser
{
    public function __construct(private SetUserActive $active) {}

    /**
     * Update a staff account without changing its password.
     *
     * @param  array{name: string, email: string, role: string, is_active?: mixed}  $attributes
     */
    public function handle(User $actor, User $user, array $attributes): User
    {
        Gate::forUser($actor)->authorize('update', $user);

        return DB::transaction(function () use ($actor, $user, $attributes): User {
            $user->name = $attributes['name'];
            $user->email = $attributes['email'];
            $user->role = Role::from($attributes['role']);
            $user->save();

            if (array_key_exists('is_active', $attributes)) {
                $this->active->handle(
                    $actor,
                    $user,
                    filter_var($attributes['is_active'], FILTER_VALIDATE_BOOLEAN),
                );
            }

            return $user;
        });
    }
}
