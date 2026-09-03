<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Support\LegacyOwnerReconciliation;
use Illuminate\Console\Command;

class ReconcileLegacyOwnerBookings extends Command
{
    protected $signature = 'bookings:reconcile-owner-postings {--apply : Apply safe unpaid-booking corrections}';

    protected $description = 'Preview or reconcile unpaid legacy owner postings; flag ambiguous records without changing them';

    public function handle(): int
    {
        $eligible = 0;
        $review = 0;
        Booking::where('owner_posting_basis', 'legacy')->chunkById(100, function ($bookings) use (&$eligible, &$review) {
            foreach ($bookings as $booking) {
                $result = $this->option('apply') ? LegacyOwnerReconciliation::reconcile($booking) : LegacyOwnerReconciliation::inspect($booking);
                $result['eligible'] ? $eligible++ : $review++;
                $this->line($booking->booking_reference.' | '.($result['eligible'] ? 'SAFE' : 'REVIEW').' | '.$result['reason']);
            }
        });
        $this->info(($this->option('apply') ? 'Reconciled: ' : 'Eligible: ').$eligible.'; requires review: '.$review);

        return self::SUCCESS;
    }
}
