<?php

namespace App\Customers\Models;

use App\Core\Enums\Role;
use App\Core\Models\Activity;
use App\Core\Models\Note;
use App\Core\Models\User;
use App\Customers\Policies\ContactPolicy;
use App\Inventory\Models\Vehicle;
use App\Sales\Models\Deal;
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
 * @property-read int $open_deals_count
 * @property-read Collection<int, Vehicle> $vehicles
 * @property-read Collection<int, Deal> $deals
 * @property-read Collection<int, Note> $notes
 * @property-read Collection<int, Activity> $activities
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
     * Deals opened with this contact.
     *
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * Notes written about this contact.
     *
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * Activity recorded against this contact.
     *
     * @return MorphMany<Activity, $this>
     */
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
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
