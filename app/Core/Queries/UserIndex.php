<?php

namespace App\Core\Queries;

use App\Core\Models\User;
use Carbon\CarbonInterface;

class UserIndex
{
    /**
     * Every staff account, ordered by name.
     *
     * @return list<array{id: int, name: string, email: string, role: string, roleLabel: string, active: bool, lastLogin: string}>
     */
    public function all(): array
    {
        return User::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user): array => $this->present($user))
            ->all();
    }

    /**
     * The add or edit panel, or null when the query does not open one.
     *
     * @return array{mode: string, id: int|null, name: string, email: string, role: string, active: bool}|null
     */
    public function panel(User $actor, ?string $panel): ?array
    {
        if ($panel === 'new') {
            if (! $actor->can('create', User::class)) {
                return null;
            }

            return [
                'mode' => 'create',
                'id' => null,
                'name' => '',
                'email' => '',
                'role' => 'rep',
                'active' => true,
            ];
        }

        if ($panel === null || ! ctype_digit($panel)) {
            return null;
        }

        $user = User::query()->find((int) $panel);

        if (! $user instanceof User || ! $actor->can('view', $user)) {
            return null;
        }

        return [
            'mode' => 'edit',
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'active' => $user->is_active,
        ];
    }

    /**
     * @return array{id: int, name: string, email: string, role: string, roleLabel: string, active: bool, lastLogin: string}
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'roleLabel' => $user->role->label(),
            'active' => $user->is_active,
            'lastLogin' => $this->lastLogin($user->last_login_at),
        ];
    }

    private function lastLogin(?CarbonInterface $at): string
    {
        if ($at === null) {
            return 'Never';
        }

        return $at->copy()->timezone((string) config('app.timezone'))->diffForHumans();
    }
}
