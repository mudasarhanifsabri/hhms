<?php

namespace App\Support;

use App\Mail\BookingPaidManagementMail;
use App\Models\BookingInvoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BookingManagementNotification
{
    public static function invoicePaid(BookingInvoice $invoice, bool $force = false): bool
    {
        $invoice = $invoice->fresh(['booking.property.building', 'booking.tenant', 'payments']);
        $managementEmail = trim((string) $invoice?->booking?->property?->building?->management_email);

        if (! $invoice || $invoice->status !== 'paid' || ! filter_var($managementEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $previouslySentAt = $invoice->management_notified_at;
        if ($force) {
            BookingInvoice::whereKey($invoice->id)->update(['management_notified_at' => now()]);
        } else {
            $claimed = BookingInvoice::query()
                ->whereKey($invoice->id)
                ->whereNull('management_notified_at')
                ->update(['management_notified_at' => now()]);

            if ($claimed === 0) {
                return false;
            }
        }

        $copyEmail = trim((string) config('hhms.management_booking_copy_email', 'customerservice@pattern.ae'));

        try {
            $mail = Mail::to($managementEmail);
            if (filter_var($copyEmail, FILTER_VALIDATE_EMAIL) && strcasecmp($copyEmail, $managementEmail) !== 0) {
                $mail->cc($copyEmail);
            }
            $mail->send(new BookingPaidManagementMail($invoice));

            $invoice->booking->histories()->create([
                'title' => $previouslySentAt ? 'Management Payment Email Resent' : 'Management Payment Email Sent',
                'description' => 'Paid invoice '.$invoice->invoice_number.' was emailed to '.$managementEmail
                    .($copyEmail && strcasecmp($copyEmail, $managementEmail) !== 0 ? ' with a copy to '.$copyEmail : '').'.',
            ]);

            return true;
        } catch (Throwable $exception) {
            BookingInvoice::whereKey($invoice->id)->update(['management_notified_at' => $previouslySentAt]);
            Log::error('Unable to send paid booking invoice email to building management.', [
                'booking_invoice_id' => $invoice->id,
                'management_email' => $managementEmail,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
