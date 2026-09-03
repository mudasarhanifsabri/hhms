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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function dashboard()
    {
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
        $owner = $request->user();
        $properties = $this->ownerProperties()->latest()->get();
        $propertyIds = $properties->pluck('id');
        $bookings = Booking::with('property.building')
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
        $monthlyRevenue = (float) $monthBookings->sum('rent_amount');
        $monthlyExpenses = (float) $expenses->filter(fn ($expense) => $expense->expense_date?->isSameMonth($now))->sum('gross_amount');
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
}
