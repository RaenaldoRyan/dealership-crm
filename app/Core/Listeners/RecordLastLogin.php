<?php

namespace App\Core\Listeners;

use App\Core\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLastLogin
{
    /**
     * Remember when this staff member finished signing in.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $user->last_login_at = now();
        $user->save();
    }
}
