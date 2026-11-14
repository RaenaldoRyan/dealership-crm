<?php

namespace App\Core\Actions;

use App\Core\Enums\Role;
use App\Core\Models\User;
use Illuminate\Support\Facades\Gate;

class CreateUser
{
    /**
     * The password given to a staff account a manager creates.
     */
    private const string InitialPassword = 'password';

    /**
     * Store a verified staff account owned by nobody but the new user.
     *
     * A posted password or verification time is ignored.
     *
     * @param  array{name: string, email: string, role: string, is_active?: mixed}  $attributes
     */
    public function handle(User $actor, array $attributes): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $user = new User;
        $user->name = $attributes['name'];
        $user->email = $attributes['email'];
        $user->password = self::InitialPassword;
        $user->role = Role::from($attributes['role']);
        $user->email_verified_at = now();
        $user->is_active = $this->active($attributes);

        $user->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function active(array $attributes): bool
    {
        if (! array_key_exists('is_active', $attributes)) {
            return true;
        }

        return filter_var($attributes['is_active'], FILTER_VALIDATE_BOOLEAN);
    }
}
