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
        $bookings = Booking::with('property')->whereIn('property_id', $propertyIds)->latest()->take(8)->get();
        $entries = LandlordAccountEntry::with('property')->where('landlord_id', Auth::id())->latest('entry_date')->take(8)->get();
        $documents = PropertyOwnerDocument::with('property')
            ->whereIn('property_id', $propertyIds)
            ->latest()
            ->take(12)
            ->get();
        $unitDocuments = UnitDocument::with(['property.building', 'owner'])
            ->whereIn('property_id', $propertyIds)
            ->latest()
            ->get();
        $balance = (float) ($entries->first()?->balance_after ?? LandlordAccountEntry::where('landlord_id', Auth::id())->latest('entry_date')->value('balance_after') ?? 0);

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
        $bookings = Booking::with(['property.building', 'invoices' => fn ($query) => $query->withSum('payments', 'amount')])
            ->whereIn('property_id', $propertyIds)
            ->latest('check_in')
            ->get();
        $entries = LandlordAccountEntry::with('property.building')
            ->where('landlord_id', $owner->id)
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
        $entries = LandlordAccountEntry::with('property.building')->where('landlord_id', $landlord->id)->statementOrder()->get();
        $credit = (float) $entries->where('direction', 'credit')->sum('amount');
        $debit = (float) $entries->where('direction', 'debit')->sum('amount');
        $accountTotals = ['credit' => $credit, 'debit' => $debit, 'balance' => $credit - $debit];
        $period = ['from' => $entries->first()?->entry_date ?? now(), 'to' => $entries->last()?->entry_date ?? now()];
        $unitStatements = $entries->groupBy(fn ($entry) => $entry->property_id ?: 'general')->map(function ($rows) {
            $running = 0;
            $rows->each(function ($entry) use (&$running) {
                $running += $entry->direction === 'credit' ? (float) $entry->amount : -(float) $entry->amount;
                $entry->setAttribute('unit_running_balance', $running);
            });

            return ['property' => $rows->first()?->property, 'entries' => $rows, 'balance' => $running];
        });

        return PdfRenderer::downloadView('admin.landlords.pdf.account-statement', compact('landlord', 'entries', 'accountTotals', 'period', 'unitStatements'), 'owner-statement-'.Str::slug($landlord->name).'.pdf', ['format' => 'A4']);
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
}
