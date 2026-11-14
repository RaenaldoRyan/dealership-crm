<?php

namespace App\Core\Policies;

use App\Core\Enums\Role;
use App\Core\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the staff list.
     */
    public function viewAny(User $user): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can view a staff record.
     */
    public function view(User $user, User $model): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can create staff.
     */
    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can edit a staff record.
     */
    public function update(User $user, User $model): bool
    {
        return $this->isManager($user);
    }

    /**
     * Determine whether the user can delete a staff record.
     *
     * Managers can remove other people. Nobody can delete themselves.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $this->isManager($user);
    }

    /**
     * Only managers look after staff accounts.
     */
    private function isManager(User $user): bool
    {
        return match ($user->role) {
            Role::Manager => true,
            Role::Rep => false,
        };
    }
}
