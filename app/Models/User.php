<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable

    {
        use HasFactory, Notifiable;
    use SoftDeletes;


    protected $keyType = 'string'; // UUIDs are strings
    public $incrementing = false; // Disable auto-incrementing
    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        });
    }
    protected $fillable = [
        'name', 'name_ar', 'email', 'phone', 'dob', 'eid_passport_no', 'address',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_email',
        'emergency_contact_relationship', 'password', 'role',
        'profile_photo', 'id_document', 'id_document_back', 'nationality', 'gender', 'id_issue_date', 'id_expiry_date',
        'bank_name', 'bank_account_holder', 'bank_account_number',
        'bank_account_type', 'swift_code', 'iban', 'bank_branch', 'agent_commission', 'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'dob' => 'date',
            'id_issue_date' => 'date',
            'id_expiry_date' => 'date',
            'password' => 'hashed',
        ];
    }

public function landlord(): BelongsTo
{
    return $this->belongsTo(User::class, 'landlord_id');
}

public function landlordAccountEntries(): HasMany
{
    return $this->hasMany(LandlordAccountEntry::class, 'landlord_id');
}

public function properties(): HasMany
{
    return $this->hasMany(Property::class, 'landlord_id');
}

public function ownedPropertyShares(): HasMany
{
    return $this->hasMany(PropertyOwnerShare::class, 'owner_id');
}

public function sharedOwnedProperties(): BelongsToMany
{
    return $this->belongsToMany(Property::class, 'property_owner_shares', 'owner_id', 'property_id')
        ->withPivot(['share_percent', 'is_primary'])
        ->withTimestamps();
}

public function agentBookings(): HasMany
{
    return $this->hasMany(Booking::class, 'agent_id');
}

}
