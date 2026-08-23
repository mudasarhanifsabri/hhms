<?php

namespace App\Models;

use App\Models\User;
use App\Models\Building;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    public const ACTIVE_STATUSES = [
        'available' => 'Available',
        'booked' => 'Booked',
        'under_cleaning' => 'Under Cleaning',
        'under_maintenance' => 'Under Maintenance',
    ];

    public const STATUSES = [
        ...self::ACTIVE_STATUSES,
        'vacant' => 'Available',
        'rented' => 'Booked',
    ];

    public const UNIT_TYPES = [
        'Studio' => 0,
        '1 BHK' => 1,
        '2 BHK' => 2,
        '3 BHK' => 3,
        '4 BHK' => 4,
        '5 BHK' => 5,
        '6 BHK' => 6,
        'Penthouse' => 4,
        'Villa' => 4,
    ];

    /**
     * Indicates that the model's ID is not auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'landlord_id',
        'building_id',
        'smartlock_id',
        'name',
        'category',
        'community',
        'rent',
        'management_fee',
        'management_fee_percent',
        'bedrooms',
        'bathrooms',
        'living_rooms',
        'kitchens',
        'square_foot',
        'floor',
        'room_no',
        'unit_floor_label',
        'parking_number',
        'description',
        'status',

        'amenities',
        'has_security',
        'security_utilities',
        'additional_features',
        'distance_to_road',
        'additional_notes',

        'photos',
        'video',
        'floor_plan',

        'dtcm_unit_permit',
        'title_deed',
        'dtcm_permit_no',
        'dtcm_permit_expiry',

        'wifi_provider',
        'wifi_name',
        'wifi_account_no',
        'wifi_password',
        'utilities_cap',
        'electricity_provider',
        'electricity_account_no',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amenities' => 'array',
        'security_utilities' => 'array',
        'additional_features' => 'array',
        'photos' => 'array',
        'has_security' => 'boolean',
        'dtcm_permit_expiry' => 'date',
        'rent' => 'decimal:2',
        'management_fee' => 'decimal:2',
        'management_fee_percent' => 'decimal:2',
        'utilities_cap' => 'decimal:2',
    ];

    /**
     * Boot function to auto-generate UUIDs.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationships
     */

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function ownerShares(): HasMany
    {
        return $this->hasMany(PropertyOwnerShare::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_owner_shares', 'property_id', 'owner_id')
            ->withPivot(['share_percent', 'is_primary'])
            ->withTimestamps();
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }


public function smartlock()
{
    return $this->belongsTo(Smartlock::class, 'smartlock_id');
}

public function ownerDocuments(): HasMany
{
    return $this->hasMany(PropertyOwnerDocument::class);
}

public function unitDocuments(): HasMany
{
    return $this->hasMany(UnitDocument::class);
}

public function bookings(): HasMany
{
    return $this->hasMany(Booking::class);
}

public function tasks(): HasMany
{
    return $this->hasMany(BookingTask::class);
}

public function utilityAccounts(): HasMany
{
    return $this->hasMany(UtilityAccount::class);
}

public function utilityBills(): HasMany
{
    return $this->hasMany(UtilityBill::class);
}

public function expenses(): HasMany
{
    return $this->hasMany(Expense::class);
}

public function accountingEntries(): HasMany
{
    return $this->hasMany(AccountingEntry::class);
}

public function getUnitLabelAttribute(): string
{
    return trim(($this->building?->name ? $this->building->name . ' - ' : '') . ($this->name ?? 'Unit'));
}

public function getStatusLabelAttribute(): string
{
    return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
}

public function getStatusClassAttribute(): string
{
    return match ($this->status) {
        'available', 'vacant' => 'bg-success',
        'booked', 'rented' => 'bg-primary',
        'under_cleaning' => 'bg-info',
        'under_maintenance' => 'bg-warning',
        default => 'bg-secondary',
    };
}

public function getUnitTypeLabelAttribute(): string
{
    if ($this->category) {
        return $this->category;
    }

    return (int) ($this->bedrooms ?? 0) === 0 ? 'Studio' : (int) $this->bedrooms . ' BHK';
}
}
