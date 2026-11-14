<?php

namespace App\Customers\Models;

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Customers\Policies\ContactPolicy;
use App\Inventory\Models\Vehicle;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read Collection<int, Vehicle> $vehicles
 */
#[UseFactory(ContactFactory::class)]
#[UsePolicy(ContactPolicy::class)]
#[Fillable(['name', 'email', 'phone'])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    /**
     * The sales rep or manager who owns this contact.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The vehicles recorded for this contact.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Limit the query to contacts the user is allowed to see.
     *
     * Managers see every contact. Everyone else sees the contacts they own.
     *
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === Role::Manager) {
            return $query;
        }

        return $query->whereBelongsTo($user, 'owner');
    }
}
