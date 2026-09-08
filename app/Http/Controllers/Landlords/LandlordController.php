<?php

namespace App\Http\Controllers\Landlords;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\PropertyOwnerDocument;
use App\Models\Expense;
use App\Models\UtilityBill;
use App\Models\BookingTask;
use App\Models\UnitDocument;
use App\Support\PdfRenderer;
use App\Support\OwnerStatementPdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LandlordController extends Controller
{
    private function ownerProperties()
    {
        return Property::with(['building', 'ownerShares', 'utilityBills.account'])
            ->where(function ($query) {
                $query->where('landlord_id', Auth::id())
                    ->orWhereHas('ownerShares', fn ($shareQuery) => $shareQuery->where('owner_id', Auth::id()));
            });
    }

    public function dashboard(Request $request)
    {
        if ($request->boolean('desktop')) {
            $request->session()->put('owner_desktop', true);
        } elseif ($this->isMobile($request) && ! (bool) $request->session()->get('owner_desktop', false)) {
            return redirect()->route('landlord.app');
        }
        $properties = Property::with(['building', 'ownerShares'])
            ->where('landlord_id', Auth::id())
            ->orWhereHas('ownerShares', fn ($query) => $query->where('owner_id', Auth::id()))
            ->latest()
            ->get();
        $propertyIds = $properties->pluck('id');
        $bookings = Booking::with(['property.building', 'invoices.payments'])->whereIn('property_id', $propertyIds)->latest()->take(8)->get();
        $this->addOwnerBookingFigures($bookings);
        $entries = LandlordAccountEntry::with('property')->where('landlord_id', Auth::id())->visibleOnOwnerStatement()->latest('entry_date')->take(8)->get();
        $documents = PropertyOwnerDocument::with('property')
            ->whereIn('property_id', $propertyIds)
            ->latest()
            ->take(12)
            ->get();
        $unitDocuments = UnitDocument::with(['property.building', 'owner'])
            ->whereIn('property_id', $propertyIds)
            ->latest()
            ->get();
        $balance = (float) LandlordAccountEntry::where('landlord_id', Auth::id())->visibleOnOwnerStatement()
            ->selectRaw("COALESCE(SUM(CASE WHEN direction='credit' THEN amount ELSE -amount END),0) balance")->value('balance');

        return view('landlord.dashboard.index', compact('properties', 'bookings', 'entries', 'documents', 'unitDocuments', 'balance'));
    }

    public function app(Request $request)
    {
        if ($request->boolean('mobile')) {
            $request->session()->forget('owner_desktop');
        }
        $owner = $request->user();
        $properties = $this->ownerProperties()->latest()->get();
        $propertyIds = $properties->pluck('id');
        $bookings = Booking::with(['property.building', 'invoices.payments'])
            ->whereIn('property_id', $propertyIds)
            ->latest('check_in')
            ->get();
        $this->addOwnerBookingFigures($bookings);
        $entries = LandlordAccountEntry::with('property.building')
            ->where('landlord_id', $owner->id)
            ->visibleOnOwnerStatement()
            ->statementOrder()
            ->get();
        $expenses = Expense::with(['property.building', 'vendor'])
            ->where('landlord_id', $owner->id)
            ->where('owner_billable', true)
            ->latest('expense_date')
            ->get();
        $utilityBills = UtilityBill::with(['property.building', 'account'])
            ->whereIn('property_id', $propertyIds)
            ->latest('bill_month')
            ->get();
        $tasks = BookingTask::with(['property.building', 'booking.property.building'])
            ->where(function ($query) use ($propertyIds) {
                $query->whereIn('property_id', $propertyIds)
                    ->orWhereHas('booking', fn ($bookingQuery) => $bookingQuery->whereIn('property_id', $propertyIds));
            })
            ->latest()
            ->get();
        $documents = PropertyOwnerDocument::with('property.building')
            ->whereIn('property_id', $propertyIds)
            ->latest()
            ->get();
        $unitDocuments = UnitDocument::with(['property.building', 'owner'])
            ->whereIn('property_id', $propertyIds)
            ->latest()
            ->get();
        $notifications = $owner->notifications()->latest()->limit(30)->get();
        $payouts = $entries->where('type', 'payout');
        $credits = (float) $entries->where('direction', 'credit')->sum('amount');
        $debits = (float) $entries->where('direction', 'debit')->sum('amount');
        $balance = $credits - $debits;
        $now = now();
        $monthBookings = $bookings->filter(fn ($booking) => $booking->check_in?->between($now->copy()->startOfMonth(), $now->copy()->endOfMonth()));
        $monthlyRevenue = (float) $entries->where('type', 'rent_income')->filter(fn ($entry) => $entry->entry_date?->isSameMonth($now))->sum('amount');
        $monthlyExpenses = (float) $entries->where('direction', 'debit')->where('type', '!=', 'management_fee')->filter(fn ($entry) => $entry->entry_date?->isSameMonth($now))->sum('amount');
        $managementFees = (float) $entries->where('type', 'management_fee')->filter(fn ($entry) => $entry->entry_date?->isSameMonth($now))->sum('amount');
        $occupiedNights = $monthBookings->sum(fn ($booking) => max(0, $booking->check_in?->diffInDays($booking->check_out) ?? 0));
        $capacityNights = max(1, $properties->count() * $now->daysInMonth);
        $occupancy = min(100, round(($occupiedNights / $capacityNights) * 100));

        return view('landlord.app.index', compact(
            'owner', 'properties', 'bookings', 'entries', 'expenses', 'utilityBills',
            'tasks', 'documents', 'unitDocuments', 'notifications', 'payouts', 'credits', 'debits',
            'balance', 'monthlyRevenue', 'monthlyExpenses', 'managementFees', 'occupancy'
        ));
    }

    public function statementPdf(Request $request)
    {
        $landlord = $request->user();
        $data = OwnerStatementPdf::data($landlord, $request->input('date_from'), $request->input('date_to'), $request->input('property_id'));

        return PdfRenderer::downloadView('admin.landlords.pdf.account-statement', $data, 'owner-statement-'.Str::slug($landlord->name).'.pdf', ['format' => 'A4']);
    }

    public function readNotifications(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marked as read.');
    }

    private function isMobile(Request $request): bool
    {
        return (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', (string) $request->userAgent());
    }

    private function addOwnerBookingFigures($bookings): void
    {
        $ownerPayouts = LandlordAccountEntry::where('landlord_id', Auth::id())->where('type', 'payout')->get();
        $bookings->each(function (Booking $booking) use ($ownerPayouts) {
            $managementRate = (float) $booking->management_fee_percent;
            $periods = $booking->invoices->sortBy('period_from')->values()->map(function ($invoice) use ($booking, $managementRate, $ownerPayouts) {
                $rentCollected = round((float) $invoice->payments->sum('rent_amount'), 2);
                $managementFee = round($rentCollected * $managementRate / 100, 2);
                $ownerNet = $rentCollected - $managementFee;
                $invoicePayouts = $ownerPayouts->filter(function ($entry) use ($booking, $invoice) {
                    if ($entry->booking_invoice_id) {
                        return $entry->booking_invoice_id === $invoice->id;
                    }
                    if ($entry->property_id && $entry->property_id !== $booking->property_id) {
                        return false;
                    }
                    $searchable = strtolower(trim(($entry->reference ?? '').' '.($entry->description ?? '')));

                    return str_contains($searchable, strtolower($invoice->invoice_number))
                        || str_contains($searchable, strtolower($booking->booking_reference));
                });
                $paidToOwner = round((float) $invoicePayouts->sum('amount'), 2);
                $rentExpected = (float) $invoice->rent_amount;
                $dueDate = $invoice->period_to ?? $booking->check_out;
                [$payoutStatus, $payoutClass] = match (true) {
                    $ownerNet > 0 && $paidToOwner >= $ownerNet => ['Paid', 'green'],
                    $paidToOwner > 0 => ['Partially paid', 'warn'],
                    $rentCollected + 0.01 < $rentExpected => ['Awaiting guest payment', 'warn'],
                    $dueDate && $dueDate->isFuture() => ['Upcoming payout', 'info'],
                    default => ['Ready for payout', 'green'],
                };
                $invoice->setAttribute('owner_rent_collected', $rentCollected);
                $invoice->setAttribute('owner_management_fee', $managementFee);
                $invoice->setAttribute('owner_net_income', $ownerNet);
                $invoice->setAttribute('owner_paid_amount', $paidToOwner);
                $invoice->setAttribute('owner_remaining_payout', max(0, $ownerNet - $paidToOwner));
                $invoice->setAttribute('owner_payout_status', $payoutStatus);
                $invoice->setAttribute('owner_payout_class', $payoutClass);
                $invoice->setAttribute('owner_payout_date', $invoicePayouts->max('entry_date'));
                $invoice->setAttribute('owner_payout_reference', $invoicePayouts->pluck('reference')->filter()->join(', '));

                return $invoice;
            });
            $booking->setRelation('invoices', $periods);
            $booking->setAttribute('owner_rent_collected', (float) $periods->sum('owner_rent_collected'));
            $booking->setAttribute('owner_management_fee', (float) $periods->sum('owner_management_fee'));
            $booking->setAttribute('owner_net_income', (float) $periods->sum('owner_net_income'));
        });
    }
}
