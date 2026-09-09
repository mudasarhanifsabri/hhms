<?php

namespace App\Http\Controllers\admin\landlords;

use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingEntry;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\LandlordAccountEntry;
use App\Models\OwnerChargeInvoice;
use App\Models\Property;
use App\Models\User;
use App\Mail\OwnerChargeInvoiceMail;
use App\Support\AppSettings;
use App\Support\PdfRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OwnerChargeInvoiceController extends Controller
{
    public function index(string $id)
    {
        $landlord = User::where('role', 'landlord')->findOrFail($id);
        $invoices = OwnerChargeInvoice::with(['property.building','lines','payments.bankAccount','expenses.vendor'])
            ->where('landlord_id', $landlord->id)->latest('invoice_date')->paginate(15);
        $summaryInvoices = OwnerChargeInvoice::with(['payments','expenses'])->where('landlord_id', $landlord->id)->get();
        $properties = $this->ownerProperties($landlord->id)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('is_active', true)->orderBy('name')->get();
        $availableExpenses = Expense::with('vendor')->whereIn('property_id', $properties->pluck('id'))
            ->whereNull('owner_charge_invoice_id')->whereIn('approval_status', ['approved','paid'])->latest('expense_date')->get();

        return view('admin.landlords.owner-invoices', compact('landlord','invoices','summaryInvoices','properties','bankAccounts','availableExpenses'));
    }

    public function store(Request $request, string $id)
    {
        $landlord = User::where('role', 'landlord')->findOrFail($id);
        $data = $request->validate([
            'property_id'=>'required|uuid|exists:properties,id','invoice_date'=>'required|date','due_date'=>'nullable|date|after_or_equal:invoice_date','notes'=>'nullable|string|max:2000',
            'lines'=>'required|array|min:1','lines.*.description'=>'required|string|max:255','lines.*.quantity'=>'required|numeric|min:0.01|max:9999','lines.*.unit_price'=>'required|numeric|min:0|max:9999999','lines.*.vat_mode'=>'required|in:none,included,excluded',
        ]);
        abort_unless($this->ownerProperties($landlord->id)->whereKey($data['property_id'])->exists(), 422, 'This unit does not belong to the owner.');
        $vatRate = (float) AppSettings::get('default_vat_rate', 5);

        DB::transaction(function () use ($data, $landlord, $vatRate) {
            $invoice = OwnerChargeInvoice::create([
                'invoice_number'=>$this->number(),'landlord_id'=>$landlord->id,'property_id'=>$data['property_id'],'invoice_date'=>$data['invoice_date'],'due_date'=>$data['due_date'] ?? null,'notes'=>$data['notes'] ?? null,'created_by'=>auth()->id(),
            ]);
            $subtotal=$vat=$total=0;
            foreach ($data['lines'] as $line) {
                $grossInput=round((float)$line['quantity']*(float)$line['unit_price'],2);
                $rate=$line['vat_mode']==='none' ? 0 : $vatRate;
                $net=$line['vat_mode']==='included' ? round($grossInput/(1+$rate/100),2) : $grossInput;
                $lineVat=$line['vat_mode']==='none' ? 0 : round($net*$rate/100,2);
                $gross=$line['vat_mode']==='included' ? $grossInput : $net+$lineVat;
                $invoice->lines()->create([...$line,'vat_rate'=>$rate,'net_amount'=>$net,'vat_amount'=>$lineVat,'gross_amount'=>$gross]);
                $subtotal+=$net; $vat+=$lineVat; $total+=$gross;
            }
            $invoice->update(['subtotal'=>$subtotal,'vat_amount'=>$vat,'total_amount'=>$total]);
            LandlordAccountEntry::create(['landlord_id'=>$landlord->id,'property_id'=>$invoice->property_id,'entry_date'=>$invoice->invoice_date,'type'=>'furnishing','direction'=>'debit','amount'=>$total,'reference'=>$invoice->invoice_number,'description'=>'Owner onboarding / setup tax invoice '.$invoice->invoice_number]);
            $this->postInvoiceAccounting($invoice);
            LandlordAccountEntry::recalculateBalancesFor($landlord->id);
        });

        return back()->with('success','Owner tax invoice created and posted to the owner statement.');
    }

    public function payment(Request $request, OwnerChargeInvoice $invoice)
    {
        $data=$request->validate(['payment_date'=>'required|date','amount'=>'required|numeric|min:0.01','method'=>'required|in:bank_transfer,cash,cash_deposit,rent_adjustment','bank_account_id'=>'nullable|required_unless:method,rent_adjustment|exists:bank_accounts,id','reference'=>'nullable|string|max:255','notes'=>'nullable|string|max:1000']);
        $invoice->load('payments');
        if ((float)$data['amount'] > $invoice->balance_due + .01) throw ValidationException::withMessages(['amount'=>'Amount cannot exceed the invoice balance.']);
        DB::transaction(function () use ($data,$invoice) {
            $payment=$invoice->payments()->create([...$data,'bank_account_id'=>$data['method']==='rent_adjustment'?null:($data['bank_account_id']??null),'created_by'=>auth()->id()]);
            if ($data['method'] !== 'rent_adjustment') {
                LandlordAccountEntry::create(['landlord_id'=>$invoice->landlord_id,'property_id'=>$invoice->property_id,'entry_date'=>$data['payment_date'],'type'=>'adjustment_credit','direction'=>'credit','amount'=>$data['amount'],'reference'=>'OCP-'.$payment->id,'description'=>'Payment received for '.$invoice->invoice_number]);
            }
            $this->postPaymentAccounting($invoice,$payment);
            $paid=(float)$invoice->payments()->sum('amount');
            $invoice->update(['status'=>$paid+.01 >= (float)$invoice->total_amount?'paid':'partially_paid']);
            LandlordAccountEntry::recalculateBalancesFor($invoice->landlord_id);
        });
        return back()->with('success','Payment recorded and allocated to the owner invoice.');
    }

    public function linkCost(Request $request, OwnerChargeInvoice $invoice)
    {
        $data=$request->validate(['expense_ids'=>'required|array|min:1','expense_ids.*'=>'uuid|exists:expenses,id']);
        Expense::whereIn('id',$data['expense_ids'])->where('property_id',$invoice->property_id)->whereNull('owner_charge_invoice_id')->update(['owner_charge_invoice_id'=>$invoice->id]);
        return back()->with('success','Supplier costs linked. Profit has been recalculated.');
    }

    public function finalizeCost(OwnerChargeInvoice $invoice)
    {
        $invoice->update(['cost_finalized'=>true]);
        return back()->with('success','Actual cost finalized.');
    }

    public function pdf(OwnerChargeInvoice $invoice)
    {
        $invoice->load(['landlord','property.building','lines','payments','expenses']);
        return PdfRenderer::downloadView('admin.landlords.pdf.owner-charge-invoice',compact('invoice'),'tax-invoice-'.$invoice->invoice_number.'.pdf',['format'=>'A4']);
    }

    public function email(Request $request, OwnerChargeInvoice $invoice)
    {
        $data=$request->validate(['recipient'=>'required|email:rfc|max:255','subject'=>'nullable|string|max:180','message'=>'nullable|string|max:2000']);
        $invoice->load(['landlord','property.building','lines','payments','expenses']);
        $filename='tax-invoice-'.$invoice->invoice_number.'.pdf';
        $pdf=PdfRenderer::output(view('admin.landlords.pdf.owner-charge-invoice',compact('invoice'))->render(),['format'=>'A4']);
        $subject=filled($data['subject'] ?? null) ? $data['subject'] : 'Owner Tax Invoice '.$invoice->invoice_number.' — '.AppSettings::get('invoice_legal_name','PATTERN Vacation Homes Rental');
        try { Mail::to($data['recipient'])->send(new OwnerChargeInvoiceMail($invoice,$subject,$data['message']??null,$pdf,$filename)); }
        catch(Throwable $e) { Log::error('Owner charge invoice email failed.',['invoice_id'=>$invoice->id,'recipient'=>$data['recipient'],'message'=>$e->getMessage()]); return back()->withErrors(['email'=>'Tax invoice email could not be sent. Please verify email settings.']); }
        return back()->with('success','Owner tax invoice emailed successfully to '.$data['recipient'].'.');
    }

    private function ownerProperties(string $id) { return Property::where(fn($q)=>$q->where('landlord_id',$id)->orWhereHas('ownerShares',fn($s)=>$s->where('owner_id',$id))); }
    private function number(): string { do {$number='OCI-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));} while(OwnerChargeInvoice::where('invoice_number',$number)->exists()); return $number; }
    private function entry(array $data): void { AccountingEntry::create(['entry_no'=>'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(7)),'approval_status'=>'posted','created_by'=>auth()->id(),...$data]); }
    private function postInvoiceAccounting(OwnerChargeInvoice $invoice): void
    {
        $base=['entry_date'=>$invoice->invoice_date,'property_id'=>$invoice->property_id,'landlord_id'=>$invoice->landlord_id,'transaction_reference'=>$invoice->invoice_number,'status'=>'posted'];
        $this->entry([...$base,'type'=>'owner','category'=>'owner_receivable','accounting_account_id'=>AccountingAccount::where('code','1060')->value('id'),'description'=>'Owner receivable '.$invoice->invoice_number,'debit'=>$invoice->total_amount,'credit'=>0,'net_amount'=>$invoice->subtotal,'vat_amount'=>$invoice->vat_amount,'gross_amount'=>$invoice->total_amount]);
        $this->entry([...$base,'type'=>'income','category'=>'owner_onboarding_income','accounting_account_id'=>AccountingAccount::where('code','4080')->value('id'),'description'=>'Owner furnishing and startup income '.$invoice->invoice_number,'debit'=>0,'credit'=>$invoice->subtotal,'net_amount'=>$invoice->subtotal,'vat_amount'=>0,'gross_amount'=>$invoice->subtotal]);
        if ((float)$invoice->vat_amount>0) $this->entry([...$base,'type'=>'vat','category'=>'output_vat','accounting_account_id'=>AccountingAccount::where('code','2040')->value('id'),'description'=>'Output VAT '.$invoice->invoice_number,'debit'=>0,'credit'=>$invoice->vat_amount,'net_amount'=>$invoice->vat_amount,'vat_amount'=>$invoice->vat_amount,'gross_amount'=>$invoice->vat_amount]);
    }
    private function postPaymentAccounting(OwnerChargeInvoice $invoice, $payment): void
    {
        $base=['entry_date'=>$payment->payment_date,'property_id'=>$invoice->property_id,'landlord_id'=>$invoice->landlord_id,'transaction_reference'=>$payment->reference ?: 'OCP-'.$payment->id,'payment_method'=>$payment->method,'status'=>'posted'];
        $this->entry([...$base,'type'=>'owner','category'=>'owner_invoice_payment','accounting_account_id'=>$payment->method==='rent_adjustment'?AccountingAccount::where('code','2020')->value('id'):$payment->bankAccount?->accounting_account_id,'paid_from_account_id'=>$payment->bank_account_id,'description'=>($payment->method==='rent_adjustment'?'Adjusted from owner rental income: ':'Payment received: ').$invoice->invoice_number,'debit'=>$payment->amount,'credit'=>0,'net_amount'=>$payment->amount,'vat_amount'=>0,'gross_amount'=>$payment->amount]);
        $this->entry([...$base,'type'=>'owner','category'=>'owner_receivable_settlement','accounting_account_id'=>AccountingAccount::where('code','1060')->value('id'),'description'=>'Receivable settled '.$invoice->invoice_number,'debit'=>0,'credit'=>$payment->amount,'net_amount'=>$payment->amount,'vat_amount'=>0,'gross_amount'=>$payment->amount]);
    }
}
