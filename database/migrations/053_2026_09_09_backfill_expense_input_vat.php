<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        $vatAccountId = DB::table('accounting_accounts')->where('code', '1070')->value('id');
        if (! $vatAccountId) return;
        DB::table('expenses')->where('vat_amount', '>', 0)->whereIn('approval_status', ['approved', 'paid'])->orderBy('id')->each(function ($expense) use ($vatAccountId) {
            DB::table('accounting_entries')->where('id', $expense->accounting_entry_id)->update(['debit' => $expense->net_amount]);
            $reference = 'VAT-COST-'.$expense->expense_no;
            if (DB::table('accounting_entries')->where('expense_id', $expense->id)->where('transaction_reference', $reference)->exists()) return;
            DB::table('accounting_entries')->insert([
                'id'=>(string)Str::uuid(),'entry_no'=>'VATCOST-'.Str::upper(Str::random(12)),'entry_date'=>$expense->expense_date,
                'type'=>'adjustment','category'=>'input_vat','accounting_account_id'=>$vatAccountId,
                'description'=>'Input VAT receivable on supplier expense '.$expense->expense_no,
                'property_id'=>$expense->property_id,'landlord_id'=>$expense->landlord_id,'booking_id'=>$expense->booking_id,'expense_id'=>$expense->id,
                'debit'=>$expense->vat_amount,'credit'=>0,'vat_rate'=>$expense->vat_rate,'vat_amount'=>$expense->vat_amount,
                'net_amount'=>$expense->vat_amount,'gross_amount'=>$expense->vat_amount,'transaction_reference'=>$reference,
                'status'=>'posted','approval_status'=>'posted','created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }
    public function down(): void { DB::table('accounting_entries')->where('transaction_reference','like','VAT-COST-%')->delete(); }
};
