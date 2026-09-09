<?php

namespace Tests\Feature;

use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\Building;
use App\Models\Expense;
use App\Models\OwnerChargeInvoice;
use App\Models\Property;
use App\Models\User;
use App\Mail\OwnerChargeInvoiceMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OwnerChargeInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_onboarding_invoice_posts_statement_income_vat_payments_and_later_cost(): void
    {
        $admin=User::factory()->create(['role'=>'admin']);
        $owner=User::factory()->create(['role'=>'landlord']);
        $building=Building::create(['building_name'=>'Beautiful Tower','address'=>'Dubai']);
        $property=Property::create(['landlord_id'=>$owner->id,'building_id'=>$building->id,'name'=>'Unit 502','status'=>'vacant']);

        $this->actingAs($admin)->post(route('admin.landlord.owner-invoices.store',$owner),[
            'property_id'=>$property->id,'invoice_date'=>'2026-09-09','due_date'=>'2026-09-16',
            'lines'=>[
                ['description'=>'Furniture package','quantity'=>1,'unit_price'=>20000,'vat_mode'=>'excluded'],
                ['description'=>'Startup and DTCM fee','quantity'=>1,'unit_price'=>3000,'vat_mode'=>'excluded'],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $invoice=OwnerChargeInvoice::with(['lines','payments','expenses'])->firstOrFail();
        $this->assertSame('23000.00',$invoice->subtotal);
        $this->assertSame('1150.00',$invoice->vat_amount);
        $this->assertSame('24150.00',$invoice->total_amount);
        $this->assertSame('pending',$invoice->cost_status);
        $this->assertDatabaseHas('landlord_account_entries',['reference'=>$invoice->invoice_number,'direction'=>'debit','amount'=>24150]);
        $this->assertDatabaseHas('accounting_entries',['transaction_reference'=>$invoice->invoice_number,'category'=>'owner_onboarding_income','credit'=>23000]);
        $this->assertDatabaseHas('accounting_entries',['transaction_reference'=>$invoice->invoice_number,'category'=>'output_vat','credit'=>1150]);

        Mail::fake();
        $this->post(route('admin.landlord.owner-invoices.email',$invoice),['recipient'=>'owner@example.com','message'=>'Your onboarding invoice is attached.'])->assertRedirect()->assertSessionHasNoErrors();
        Mail::assertSent(OwnerChargeInvoiceMail::class,fn(OwnerChargeInvoiceMail $mail)=>$mail->hasTo('owner@example.com') && str_starts_with($mail->pdfContent,'%PDF-') && count($mail->attachments())===1);

        $bank=BankAccount::create(['accounting_account_id'=>AccountingAccount::where('code','1010')->value('id'),'name'=>'Operating Bank','type'=>'bank','currency'=>'AED','is_active'=>true]);
        $this->post(route('admin.landlord.owner-invoices.payment',$invoice),['payment_date'=>'2026-09-10','amount'=>5000,'method'=>'bank_transfer','bank_account_id'=>$bank->id,'reference'=>'BANK-001'])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.landlord.owner-invoices.payment',$invoice),['payment_date'=>'2026-09-11','amount'=>3000,'method'=>'rent_adjustment','reference'=>'RENT-ADJ'])->assertRedirect()->assertSessionHasNoErrors();
        $invoice->refresh()->load(['payments','expenses']);
        $this->assertSame('partially_paid',$invoice->status);
        $this->assertSame(16150.0,$invoice->balance_due);
        $this->assertDatabaseHas('landlord_account_entries',['reference'=>'OCP-'.$invoice->payments->first()->id,'direction'=>'credit','amount'=>5000]);
        $this->assertDatabaseMissing('landlord_account_entries',['description'=>'Adjusted from owner rental income: '.$invoice->invoice_number]);

        $expense=Expense::create(['expense_no'=>'EXP-COST-1','expense_date'=>'2026-09-12','category'=>'supplies','property_id'=>$property->id,'responsibility'=>'company','net_amount'=>12000,'vat_rate'=>5,'vat_amount'=>600,'gross_amount'=>12600,'approval_status'=>'approved','created_by'=>$admin->id]);
        $this->post(route('admin.landlord.owner-invoices.cost',$invoice),['expense_ids'=>[$expense->id]])->assertRedirect()->assertSessionHasNoErrors();
        $invoice->refresh()->load(['payments','expenses']);
        $this->assertSame('provisional',$invoice->cost_status);
        $this->assertSame(12000.0,$invoice->actual_cost);
        $this->assertSame(11000.0,$invoice->profit);
        $this->post(route('admin.landlord.owner-invoices.cost.finalize',$invoice))->assertRedirect();
        $this->assertSame('final',$invoice->refresh()->load('expenses')->cost_status);
        $this->get(route('admin.landlord.owner-invoices.pdf',$invoice))->assertOk()->assertHeader('content-type','application/pdf');
        $this->get(route('admin.landlord.owner-invoices',$owner))->assertOk()->assertSee('Owner Invoice Register')->assertSee($invoice->invoice_number);
    }
}
