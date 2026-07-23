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
        'name', 'email', 'phone', 'dob', 'eid_passport_no', 'address',
        'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_email',
        'emergency_contact_relationship', 'password', 'role',
        'profile_photo', 'id_document','bank_name', 'bank_account_holder', 'bank_account_number',
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

public function agentBookings(): HasMany
{
    return $this->hasMany(Booking::class, 'agent_id');
}

}
