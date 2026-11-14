<?php

namespace App\Core\Auth;

use App\Core\Models\User;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AuthenticateActiveUser
{
    public const string InactiveMessage = 'This account is inactive. Ask a manager if you need access.';

    /**
     * Return the staff member when the password matches and the account is active.
     *
     * A wrong password returns null so Fortify can show its usual message.
     * A correct password on an inactive account explains why sign-in stopped.
     */
    public function __invoke(Request $request): ?User
    {
        $email = Str::lower($request->string(Fortify::username())->toString());
        $password = $request->string('password')->toString();

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User || ! Hash::check($password, $user->password)) {
            return null;
        }

        $provider = Auth::guard((string) config('fortify.guard'))->getProvider();

        if (config('hashing.rehash_on_login', true) && $provider instanceof UserProvider && method_exists($provider, 'rehashPasswordIfRequired')) {
            $provider->rehashPasswordIfRequired($user, ['password' => $password]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                Fortify::username() => self::InactiveMessage,
            ]);
        }

        return $user;
    }
}
