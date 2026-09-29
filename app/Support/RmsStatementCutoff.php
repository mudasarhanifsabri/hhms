<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingInvoicePayment;
use Illuminate\Database\Eloquent\Builder;

class RmsStatementCutoff
{
    public const DATE = '2026-09-01';

    public static function applies(): bool
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return in_array($host, ['rms.pattern.ae', 'rms.dt-server.com'], true);
    }

    public static function bookings(Builder $query): Builder
    {
        return $query->when(self::applies(), fn (Builder $query) => $query->whereDate('check_in', '>=', self::DATE));
    }

    public static function ownerEntries(Builder $query): Builder
    {
        if (! self::applies()) {
            return $query;
        }

        $legacyBookingIds = Booking::query()->whereDate('check_in', '<', self::DATE)->pluck('id');
        if ($legacyBookingIds->isEmpty()) {
            return $query;
        }

        $legacyInvoices = BookingInvoice::query()
            ->whereIn('booking_id', $legacyBookingIds)
            ->get(['id', 'invoice_number']);
        $legacyInvoiceIds = $legacyInvoices->pluck('id');
        $legacyReferences = $legacyInvoices->pluck('invoice_number')
            ->concat(Booking::query()->whereIn('id', $legacyBookingIds)->pluck('booking_reference'))
            ->concat(BookingInvoicePayment::query()->whereIn('booking_invoice_id', $legacyInvoiceIds)->pluck('id')->map(fn ($id) => 'PAY-'.$id))
            ->filter()->unique()->values();

        return $query
            ->where(fn (Builder $entry) => $entry->whereNull('booking_invoice_id')->orWhereNotIn('booking_invoice_id', $legacyInvoiceIds))
            ->when($legacyReferences->isNotEmpty(), fn (Builder $entry) => $entry->where(fn (Builder $reference) => $reference
                ->whereNull('reference')
                ->orWhereNotIn('reference', $legacyReferences)));
    }
}
