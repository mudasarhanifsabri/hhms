<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ExpenseAudit extends BaseModel
{
    protected $fillable=['expense_id','user_id','action','reason','before_values','after_values','ip_address'];
    protected $casts=['before_values'=>'array','after_values'=>'array'];
    public function expense(): BelongsTo { return $this->belongsTo(Expense::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
