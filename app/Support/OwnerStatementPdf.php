<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;

class OwnerStatementPdf
{
    public static function data(User $owner, ?string $from = null, ?string $to = null, ?string $propertyId = null): array
    {
        $base = LandlordAccountEntry::with('property.building')->where('landlord_id', $owner->id);
        $first = (clone $base)->oldest('entry_date')->value('entry_date');
        $last = (clone $base)->latest('entry_date')->value('entry_date');
        $period = [
            'from' => Carbon::parse($from ?: ($first ?: now()->startOfMonth())),
            'to' => Carbon::parse($to ?: ($last ?: now()->endOfMonth())),
        ];
        $openingBalance = (float) LandlordAccountEntry::where('landlord_id', $owner->id)
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->whereDate('entry_date', '<', $period['from'])
            ->selectRaw("COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END),0) balance")
            ->value('balance');
        $entries = $base->whereBetween('entry_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->statementOrder()->get();
        $credit = (float) $entries->where('direction', 'credit')->sum('amount');
        $debit = (float) $entries->where('direction', 'debit')->sum('amount');
        $running = $openingBalance;
        $entries->each(function ($entry) use (&$running) {
            $running += $entry->direction === 'credit' ? (float) $entry->amount : -(float) $entry->amount;
            $entry->setAttribute('statement_balance', $running);
        });
        $propertyIds = $propertyId
            ? collect([$propertyId])
            : Property::where('landlord_id', $owner->id)
                ->orWhereHas('ownerShares', fn ($query) => $query->where('owner_id', $owner->id))
                ->pluck('id');
        $reservations = Booking::with(['property.building', 'invoices.payments'])
            ->whereIn('property_id', $propertyIds)
            ->whereDate('check_in', '<=', $period['to'])
            ->whereDate('check_out', '>=', $period['from'])
            ->orderBy('check_in')->get()
            ->flatMap(function (Booking $booking) {
                return $booking->invoices->sortBy('period_from')->map(function ($invoice) use ($booking) {
                    $receivedRent = (float) $invoice->payments->sum('rent_amount');
                    $management = round($receivedRent * (float) $booking->management_fee_percent / 100, 2);
                    $invoice->setRelation('booking', $booking);
                    $invoice->setAttribute('statement_net_rent', $receivedRent - $management);

                    return $invoice;
                });
            });

        return [
            'landlord' => $owner,
            'period' => $period,
            'entries' => $entries,
            'reservations' => $reservations,
            'openingBalance' => $openingBalance,
            'accountTotals' => ['credit' => $credit, 'debit' => $debit, 'balance' => $running],
            'summary' => [
                'rent' => (float) $entries->where('type', 'rent_income')->where('direction', 'credit')->sum('amount'),
                'management' => (float) $entries->where('type', 'management_fee')->where('direction', 'debit')->sum('amount'),
                'maintenance' => (float) $entries->where('type', 'maintenance')->where('direction', 'debit')->sum('amount'),
                'expenses' => (float) $entries->where('direction', 'debit')->whereNotIn('type', ['management_fee', 'maintenance', 'payout'])->sum('amount'),
                'payouts' => (float) $entries->where('type', 'payout')->where('direction', 'debit')->sum('amount'),
            ],
        ];
    }
}
