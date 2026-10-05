<?php

namespace Tests\Feature;

use App\Mail\BookingPaidManagementMail;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Support\BookingManagementNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingManagementNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_invoice_is_emailed_once_to_management_and_customer_service_with_documents(): void
    {
        Mail::fake();
        Storage::fake('public');
        config()->set('hhms.media_disk', 'public');
        config()->set('hhms.management_booking_copy_email', 'customerservice@pattern.ae');

        Storage::disk('public')->put('tenant/guest.pdf', 'guest');
        Storage::disk('public')->put('tenant/front.jpg', 'front');
        Storage::disk('public')->put('tenant/back.jpg', 'back');

        $owner = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create([
            'role' => 'tenant',
            'id_document' => 'tenant/front.jpg',
            'id_document_back' => 'tenant/back.jpg',
        ]);
        $building = Building::create([
            'building_name' => 'Vida Dubai Mall Tower 2',
            'management_email' => 'management@example.com',
            'address' => 'Downtown Dubai',
        ]);
        $property = Property::create([
            'landlord_id' => $owner->id,
            'building_id' => $building->id,
            'name' => '501',
            'status' => 'rented',
        ]);
        $booking = Booking::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'booking_reference' => 'BK-MGT-001',
            'guest_name' => 'Test Guest',
            'guest_email' => 'guest@example.com',
            'guest_phone' => '+971500000000',
            'guest_passport_id_no' => 'P1234567',
            'guest_document' => 'tenant/guest.pdf',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-20',
            'status' => 'confirmed',
            'invoice_number' => 'INV-MGT-001',
            'invoice_status' => 'paid',
        ]);
        $invoice = BookingInvoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-MGT-001',
            'invoice_type' => 'original',
            'issue_date' => '2026-10-01',
            'period_from' => '2026-10-10',
            'period_to' => '2026-10-20',
            'rent_amount' => 5000,
            'total_amount' => 5000,
            'status' => 'paid',
        ]);
        $invoice->payments()->create([
            'payment_date' => '2026-10-05',
            'amount' => 5000,
            'payment_method' => 'Bank Transfer',
        ]);

        BookingManagementNotification::invoicePaid($invoice);
        BookingManagementNotification::invoicePaid($invoice);

        Mail::assertSent(BookingPaidManagementMail::class, 1);
        Mail::assertSent(BookingPaidManagementMail::class, function (BookingPaidManagementMail $mail) {
            return $mail->hasTo('management@example.com')
                && $mail->hasCc('customerservice@pattern.ae')
                && count($mail->attachments()) === 3
                && str_contains($mail->render(), '10 Oct 2026')
                && str_contains($mail->render(), '20 Oct 2026');
        });
        $this->assertNotNull($invoice->fresh()->management_notified_at);
        $this->assertDatabaseHas('booking_histories', [
            'booking_id' => $booking->id,
            'title' => 'Management Payment Email Sent',
        ]);

        BookingManagementNotification::invoicePaid($invoice, force: true);
        Mail::assertSent(BookingPaidManagementMail::class, 2);
        $this->assertDatabaseHas('booking_histories', [
            'booking_id' => $booking->id,
            'title' => 'Management Payment Email Resent',
        ]);
    }

    public function test_unpaid_invoice_is_not_emailed(): void
    {
        Mail::fake();
        $invoice = new BookingInvoice(['status' => 'unpaid']);

        BookingManagementNotification::invoicePaid($invoice);

        Mail::assertNothingSent();
    }
}
