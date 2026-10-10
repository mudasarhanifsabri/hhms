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
        $base = LandlordAccountEntry::with(['property.building', 'bookingInvoice'])->where('landlord_id', $owner->id)->visibleOnOwnerStatement();
        $datedEntries = (clone $base)->get();
        $first = $datedEntries->min(fn ($entry) => $entry->statement_date);
        $last = $datedEntries->max(fn ($entry) => $entry->statement_date);
        $period = [
            'from' => Carbon::parse($from ?: ($first ?: now()->startOfMonth())),
            'to' => Carbon::parse($to ?: ($last ?: now()->endOfMonth())),
        ];
        $openingBalance = (float) LandlordAccountEntry::where('landlord_id', $owner->id)
            ->visibleOnOwnerStatement()
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->beforeStatementDate($period['from']->toDateString())
            ->selectRaw("COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END),0) balance")
            ->value('balance');
        $entries = $base->forStatementPeriod($period['from']->toDateString(), $period['to']->toDateString())
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
            ->when(RmsStatementCutoff::applies(), fn ($query) => $query->whereHas('invoices', fn ($invoice) => RmsStatementCutoff::invoices($invoice)))
            ->whereDate('check_in', '<=', $period['to'])
            ->whereDate('check_out', '>=', $period['from'])
            ->orderBy('check_in')->get()
            ->flatMap(function (Booking $booking) use ($period) {
                return $booking->invoices->sortBy('period_from')
                    ->when(RmsStatementCutoff::applies(), fn ($invoices) => $invoices->filter(fn ($invoice) => RmsStatementCutoff::includesInvoice($invoice)))
                    // Each paid invoice belongs to the statement month in which its
                    // service period starts. Do not repeat an August invoice in the
                    // September reservation summary merely because it overlaps it.
                    ->filter(fn ($invoice) => $invoice->period_from?->betweenIncluded($period['from'], $period['to']))
                    ->filter(fn ($invoice) => (float) $invoice->payments->sum('amount') + 0.01 >= (float) $invoice->total_amount)
                    ->map(function ($invoice) use ($booking) {
                        $receivedRent = (float) $invoice->payments->sum('rent_amount');
                        $management = round($receivedRent * (float) $booking->management_fee_percent / 100, 2);
                        $invoice->setRelation('booking', $booking);
                        $invoice->setAttribute('statement_rent_received', $receivedRent);
                        $invoice->setAttribute('statement_management_fee', $management);
                        $invoice->setAttribute('statement_net_rent', $receivedRent - $management);

                        return $invoice;
                    });
            });

        $ownerExpenseEntries = $entries->where('direction', 'debit')->whereNotIn('type', ['management_fee', 'payout']);
        $ownerExpenseBreakdown = $ownerExpenseEntries->groupBy('type')->map(function ($typeEntries) {
            return [
                'label' => $typeEntries->first()->type_label,
                'amount' => (float) $typeEntries->sum('amount'),
            ];
        })->values();
        $rentIncome = (float) $entries->where('type', 'rent_income')->where('direction', 'credit')->sum('amount');
        $managementFees = (float) $entries->where('type', 'management_fee')->where('direction', 'debit')->sum('amount');
        $ownerExpenses = (float) $ownerExpenseEntries->sum('amount');
        $payouts = (float) $entries->where('type', 'payout')->where('direction', 'debit')->sum('amount');

        return [
            'landlord' => $owner,
            'period' => $period,
            'entries' => $entries,
            'reservations' => $reservations,
            'openingBalance' => $openingBalance,
            'accountTotals' => ['credit' => $credit, 'debit' => $debit, 'balance' => $running],
            'summary' => [
                'rent' => $rentIncome,
                'management' => $managementFees,
                'owner_expenses' => $ownerExpenses,
                'expense_breakdown' => $ownerExpenseBreakdown,
                'payouts' => $payouts,
                'other_credits' => max(0, $credit - $rentIncome),
                'other_debits' => max(0, $debit - $managementFees - $ownerExpenses - $payouts),
                'period_movement' => $credit - $debit,
            ],
        ];
    }
}
