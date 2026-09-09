<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OwnerChargeInvoiceLine extends BaseModel
{
    protected $fillable=['owner_charge_invoice_id','description','quantity','unit_price','vat_mode','vat_rate','net_amount','vat_amount','gross_amount'];
    protected $casts=['quantity'=>'decimal:2','unit_price'=>'decimal:2','vat_rate'=>'decimal:2','net_amount'=>'decimal:2','vat_amount'=>'decimal:2','gross_amount'=>'decimal:2'];
    public function invoice(): BelongsTo { return $this->belongsTo(OwnerChargeInvoice::class,'owner_charge_invoice_id'); }
}
