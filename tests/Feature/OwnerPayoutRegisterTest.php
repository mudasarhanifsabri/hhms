<?php

namespace Tests\Feature;

use App\Mail\OwnerPayoutReceiptMail;
use App\Models\EmailDelivery;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerPayoutRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_payouts_are_listed_on_the_separate_register(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'name' => 'Register Owner']);
        LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'entry_date' => '2026-10-10', 'type' => 'payout',
            'direction' => 'debit', 'amount' => 7250, 'reference' => 'LEGACY-PAYOUT-100',
            'description' => 'September owner settlement',
        ]);

        $this->actingAs($admin)->get(route('admin.accounting.owner-payouts'))
            ->assertOk()->assertSee('All Recorded Owner Payouts')->assertSee('Register Owner')
            ->assertSee('LEGACY-PAYOUT-100')->assertSee('7,250.00')->assertSee('Completed')
            ->assertSee('Showing 1–1 of 1 recorded payouts');
    }

    public function test_admin_can_record_a_detailed_owner_transfer_and_email_receipt(): void
    {
        Mail::fake();
        Storage::fake('public');
        config()->set('hhms.media_disk', 'public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'email' => 'payout-owner@example.com']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Payout Unit 707', 'status' => 'vacant']);

        $this->actingAs($admin)->post(route('admin.accounting.owner-payouts.store'), [
            'landlord_id' => $owner->id,
            'property_id' => $property->id,
            'entry_date' => '2026-10-14',
            'amount' => 5200.50,
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-TXN-520050',
            'description' => 'September net rental payout',
            'send_email' => 1,
            'receipt' => UploadedFile::fake()->create('transfer-receipt.pdf', 80, 'application/pdf'),
        ])->assertRedirect()->assertSessionHas('success');

        $payout = LandlordAccountEntry::where('reference', 'BANK-TXN-520050')->sole();
        $this->assertSame('payout', $payout->type);
        $this->assertSame('completed', $payout->transfer_status);
        $this->assertSame('bank_transfer', $payout->payment_method);
        $this->assertSame((string) $admin->id, $payout->created_by);
        $this->assertNotNull($payout->receipt_attachment);
        Mail::assertSent(OwnerPayoutReceiptMail::class, fn ($mail) => $mail->hasTo('payout-owner@example.com'));
    }

    public function test_admin_can_record_owner_transfer_without_sending_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'email' => 'quiet-owner@example.com']);

        $this->actingAs($admin)->post(route('admin.accounting.owner-payouts.store'), [
            'landlord_id' => $owner->id,
            'entry_date' => '2026-10-14',
            'amount' => 1200,
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-NO-EMAIL-1200',
            'send_email' => 0,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('landlord_account_entries', ['reference' => 'BANK-NO-EMAIL-1200', 'type' => 'payout']);
        Mail::assertNothingSent();
    }

    public function test_owner_payout_email_is_recorded_with_owner_and_unit(): void
    {
        config()->set('mail.default', 'array');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'email' => 'logged-owner@example.com']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Logged Unit 808', 'status' => 'vacant']);

        $this->actingAs($admin)->post(route('admin.accounting.owner-payouts.store'), [
            'landlord_id' => $owner->id,
            'property_id' => $property->id,
            'entry_date' => '2026-10-14',
            'amount' => 2500,
            'payment_method' => 'bank_transfer',
            'reference' => 'BANK-LOGGED-2500',
            'send_email' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $delivery = EmailDelivery::query()->sole();
        $this->assertSame('owner_payout', $delivery->type);
        $this->assertSame('sent', $delivery->status);
        $this->assertSame((string) $owner->id, (string) $delivery->landlord_id);
        $this->assertSame((string) $property->id, (string) $delivery->property_id);
        $this->assertSame(['logged-owner@example.com'], $delivery->recipients);
    }
}
