<?php

namespace App\Core\Actions;

use App\Core\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SetUserActive
{
    /**
     * Turn a staff account on or off.
     *
     * A manager cannot deactivate the account they are signed in with.
     */
    public function handle(User $actor, User $user, bool $active): User
    {
        Gate::forUser($actor)->authorize('update', $user);

        if ($actor->is($user) && ! $active) {
            throw ValidationException::withMessages([
                'is_active' => 'You cannot deactivate your own account.',
            ]);
        }

        $user->is_active = $active;
        $user->save();

        return $user;
    }
}
