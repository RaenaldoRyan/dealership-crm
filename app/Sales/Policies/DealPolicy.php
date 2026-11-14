<?php

namespace App\Sales\Policies;

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Sales\Models\Deal;

class DealPolicy
{
    /**
     * Determine whether the user can view any deals.
     */
    public function viewAny(User $user): bool
    {
        return $this->worksWithDeals($user);
    }

    /**
     * Determine whether the user can view the deal.
     */
    public function view(User $user, Deal $deal): bool
    {
        return $this->canManage($user, $deal);
    }

    /**
     * Determine whether the user can create deals.
     */
    public function create(User $user): bool
    {
        return $this->worksWithDeals($user);
    }

    /**
     * Determine whether the user can update the deal.
     */
    public function update(User $user, Deal $deal): bool
    {
        return $this->canManage($user, $deal);
    }

    /**
     * Sales reports are for managers.
     */
    public function viewReports(User $user): bool
    {
        return $user->role === Role::Manager;
    }

    /**
     * Reps and managers are the roles that work deals.
     */
    private function worksWithDeals(User $user): bool
    {
        return match ($user->role) {
            Role::Rep, Role::Manager => true,
        };
    }

    /**
     * Managers can manage every deal. Reps can manage their own.
     */
    private function canManage(User $user, Deal $deal): bool
    {
        return match ($user->role) {
            Role::Manager => true,
            Role::Rep => $deal->user_id === $user->id,
        };
    }
}
