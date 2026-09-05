<?php

use App\Models\Booking;
use App\Support\BookingTenantProfile;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Booking::query()
            ->orderBy('id')
            ->chunkById(100, function ($bookings): void {
                $bookings->each(fn (Booking $booking) => BookingTenantProfile::sync($booking));
            });
    }

    public function down(): void
    {
        // Tenant profiles may contain user-entered data and must not be removed.
    }
};
