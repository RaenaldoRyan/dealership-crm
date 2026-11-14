<?php

namespace App\Sales\Models;

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Policies\DealPolicy;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contact_id
 * @property int|null $vehicle_id
 * @property int $user_id
 * @property string $value
 * @property DealStage $stage
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact $contact
 * @property-read Vehicle|null $vehicle
 * @property-read User $owner
 */
#[UseFactory(DealFactory::class)]
#[UsePolicy(DealPolicy::class)]
#[Fillable(['contact_id', 'vehicle_id', 'value', 'stage'])]
class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory;

    /**
     * The contact this deal is with.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * The vehicle included in this deal, when one was chosen.
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The sales rep or manager who owns this deal.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Limit the query to deals the user is allowed to see.
     *
     * Managers see every deal. Everyone else sees the deals they own.
     *
     * @param  Builder<Deal>  $query
     * @return Builder<Deal>
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === Role::Manager) {
            return $query;
        }

        return $query->whereBelongsTo($user, 'owner');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'stage' => DealStage::class,
        ];
    }
}
