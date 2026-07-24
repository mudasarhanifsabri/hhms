<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class UtilityAccount extends BaseModel
{
    public const TYPES = [
        'dewa' => 'DEWA',
        'gas' => 'Gas',
        'internet' => 'Internet',
        'chiller' => 'Chiller',
        'other' => 'Other',
    ];

    public const RESPONSIBILITIES = [
        'owner' => 'Owner',
        'company' => 'Company',
        'tenant_guest' => 'Tenant / Guest',
    ];

    protected $fillable = [
        'property_id',
        'utility_type',
        'responsibility',
        'supplier',
        'account_number',
        'username',
        'password_encrypted',
        'contract_number',
        'connection_status',
        'connection_start_date',
        'contract_expiry_date',
        'billing_day',
        'notes',
    ];

    protected $casts = [
        'connection_start_date' => 'date',
        'contract_expiry_date' => 'date',
    ];

    protected function portalPassword(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->password_encrypted ? Crypt::decryptString($this->password_encrypted) : null,
            set: fn ($value) => ['password_encrypted' => filled($value) ? Crypt::encryptString($value) : $this->password_encrypted],
        );
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->utility_type] ?? ucfirst($this->utility_type);
    }

    public function getResponsibilityLabelAttribute(): string
    {
        return self::RESPONSIBILITIES[$this->responsibility] ?? ucfirst($this->responsibility);
    }
}
