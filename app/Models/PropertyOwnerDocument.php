<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyOwnerDocument extends BaseModel
{
    public const TYPES = [
        'noc' => 'No Objection Certificate',
        'management_letter' => 'Property Management Letter',
        'management_contract' => 'Property Management Contract',
    ];

    protected $fillable = [
        'property_id',
        'landlord_id',
        'package_id',
        'agreement_type',
        'type',
        'title',
        'reference_no',
        'status',
        'signing_token',
        'company_token',
        'furniture_amount',
        'startup_dtcm_fee',
        'vat_amount',
        'total_amount',
        'unsigned_html',
        'signed_html',
        'signature_data',
        'owner_signed_name',
        'company_signature_data',
        'company_signed_name',
        'company_signer_email',
        'signed_document_path',
        'sent_at',
        'viewed_at',
        'signed_at',
        'company_sent_at',
        'company_viewed_at',
        'company_signed_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'furniture_amount' => 'decimal:2',
        'startup_dtcm_fee' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'company_sent_at' => 'datetime',
        'company_viewed_at' => 'datetime',
        'company_signed_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getAgreementTypeLabelAttribute(): string
    {
        return $this->agreement_type === 'renewal' ? 'Renewal Agreement' : 'New Agreement';
    }
}
