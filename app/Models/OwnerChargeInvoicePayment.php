<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OwnerChargeInvoicePayment extends BaseModel
{
    protected $fillable=['owner_charge_invoice_id','payment_date','amount','method','bank_account_id','reference','notes','created_by'];
    protected $casts=['payment_date'=>'date','amount'=>'decimal:2'];
    public function invoice(): BelongsTo { return $this->belongsTo(OwnerChargeInvoice::class,'owner_charge_invoice_id'); }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
}
