<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('booking_invoices')
            ->select(['id', 'booking_id', 'invoice_type', 'period_from', 'period_to'])
            ->orderBy('booking_id')
            ->orderBy('period_from')
            ->orderBy('created_at')
            ->get()
            ->groupBy('booking_id')
            ->each(function ($invoices): void {
                $previousPeriodTo = null;

                foreach ($invoices as $invoice) {
                    if (
                        $invoice->invoice_type === 'extension'
                        && $previousPeriodTo
                        && Carbon::parse($invoice->period_from)->isSameDay(Carbon::parse($previousPeriodTo)->addDay())
                    ) {
                        DB::table('booking_invoices')->where('id', $invoice->id)->update([
                            'period_from' => Carbon::parse($previousPeriodTo)->toDateString(),
                        ]);
                    }

                    $previousPeriodTo = $invoice->period_to;
                }
            });
    }

    public function down(): void
    {
        // This is a one-time correction of historical invoice boundaries.
    }
};
