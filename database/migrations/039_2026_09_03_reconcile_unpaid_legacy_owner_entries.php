<?php

use App\Models\Booking;
use App\Support\LegacyOwnerReconciliation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Booking::where('owner_posting_basis', 'legacy')->chunkById(100, function ($bookings) {
            foreach ($bookings as $booking) {
                LegacyOwnerReconciliation::reconcile($booking);
            }
        });
    }

    public function down(): void
    {
        // Financial audit corrections are intentionally retained on rollback.
    }
};
