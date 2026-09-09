<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OwnerChargeInvoice extends BaseModel
{
    protected $fillable = ['invoice_number','landlord_id','property_id','invoice_date','due_date','notes','subtotal','vat_amount','total_amount','status','cost_finalized','created_by'];
    protected $casts = ['invoice_date'=>'date','due_date'=>'date','subtotal'=>'decimal:2','vat_amount'=>'decimal:2','total_amount'=>'decimal:2','cost_finalized'=>'boolean'];
    public function landlord(): BelongsTo { return $this->belongsTo(User::class, 'landlord_id'); }
    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function lines(): HasMany { return $this->hasMany(OwnerChargeInvoiceLine::class); }
    public function payments(): HasMany { return $this->hasMany(OwnerChargeInvoicePayment::class); }
    public function expenses(): HasMany { return $this->hasMany(Expense::class); }
    public function getPaidAmountAttribute(): float { return (float) $this->payments->sum('amount'); }
    public function getBalanceDueAttribute(): float { return max(0, (float) $this->total_amount - $this->paid_amount); }
    public function getActualCostAttribute(): float { return (float) $this->expenses->sum('net_amount'); }
    public function getProfitAttribute(): float { return (float) $this->subtotal - $this->actual_cost; }
    public function getCostStatusAttribute(): string { return $this->cost_finalized ? 'final' : ($this->expenses->isEmpty() ? 'pending' : 'provisional'); }
}
