<?php

use App\Models\Booking;
use App\Models\BookingInvoicePayment;
use App\Support\InvoiceAllocationRebuilder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        BookingInvoicePayment::query()->whereNotNull('allocation')->with('invoice:id,booking_id')->get()
            ->pluck('invoice.booking_id')->filter()->unique()->each(function ($bookingId) {
                $booking = Booking::find($bookingId);
                if ($booking) {
                    InvoiceAllocationRebuilder::rebuild($booking);
                }
            });
    }

    public function down(): void
    {
        // Financial allocations cannot be safely reverted to the former proportional policy.
    }
};
