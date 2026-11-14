<?php

namespace App\Inventory\Models;

use App\Customers\Models\Contact;
use App\Inventory\Enums\FuelType;
use App\Inventory\Enums\Transmission;
use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Policies\VehiclePolicy;
use App\Inventory\Support\Registration;
use App\Sales\Models\Deal;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $contact_id
 * @property string $make
 * @property string $model
 * @property string|null $colour
 * @property FuelType|null $fuel_type
 * @property Transmission|null $transmission
 * @property int $year
 * @property int|null $mileage
 * @property string $registration
 * @property string|null $vin
 * @property string $price
 * @property VehicleStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Contact|null $contact
 * @property-read Deal|null $latestDeal
 * @property-read Collection<int, Deal> $deals
 */
#[UseFactory(VehicleFactory::class)]
#[UsePolicy(VehiclePolicy::class)]
#[Fillable(['contact_id', 'make', 'model', 'colour', 'fuel_type', 'transmission', 'year', 'mileage', 'registration', 'vin', 'price', 'status'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'in_stock',
    ];

    /**
     * The contact this vehicle belongs to, when it is not stock.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Deals that include this vehicle.
     *
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * The newest deal recorded against this vehicle.
     *
     * @return HasOne<Deal, $this>
     */
    public function latestDeal(): HasOne
    {
        return $this->hasOne(Deal::class)->latestOfMany();
    }

    /**
     * Store the plate without spaces, in upper case.
     *
     * @return Attribute<string, string>
     */
    protected function registration(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Registration::normalize($value),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage' => 'integer',
            'price' => 'decimal:2',
            'fuel_type' => FuelType::class,
            'transmission' => Transmission::class,
            'status' => VehicleStatus::class,
        ];
    }
}
