<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Property;
use App\Models\UnitDocument;
use App\Models\User;
use App\Models\FinancialApprovalRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();
        $in30 = $today->copy()->addDays(30);

        $userCounts = User::query()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        // The latest wallet permit supersedes historical uploads and legacy unit fields.
        $permitExpiry = UnitDocument::query()->select('expires_at')
            ->whereColumn('property_id', 'properties.id')->where('type', 'dtcm_permit')
            ->orderByDesc('created_at')->orderByDesc('id')->limit(1);
        $units = Property::query()->select('properties.*')->selectSub($permitExpiry, 'wallet_expiry');
        $propertyStats = DB::query()->fromSub($units, 'units')
            ->selectRaw("
                COUNT(*) as total_properties,
                SUM(CASE WHEN status IN ('booked', 'rented') THEN 1 ELSE 0 END) as rented_properties,
                SUM(CASE WHEN status IN ('available', 'vacant') THEN 1 ELSE 0 END) as vacant_properties,
                SUM(CASE WHEN wallet_expiry BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_dtcm
            ", [$today->toDateString(), $in30->toDateString()])
            ->first();

        $totalProperties = (int) ($propertyStats->total_properties ?? 0);
        $propertiesRented = (int) ($propertyStats->rented_properties ?? 0);
        $propertiesVacant = (int) ($propertyStats->vacant_properties ?? 0);
        $upcomingDtcmExpiry = (int) ($propertyStats->expiring_dtcm ?? 0);
        $liveBookings = Booking::query()->whereHas('property');
        // A unit remains operationally occupied from its check-in date until staff
        // explicitly complete checkout. This also covers confirmed bookings where
        // the check-in button was missed; future reservations are not occupancy.
        $occupiedUnits = (clone $liveBookings)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in', '<=', $today)
            ->distinct()->count('property_id');
        $emptyUnits = max(0, $totalProperties - $occupiedUnits);
        $occupancyPercent = $totalProperties > 0 ? round($occupiedUnits / $totalProperties * 100) : 0;
        $arrivalsToday = (clone $liveBookings)->where('status', 'confirmed')->whereDate('check_in', $today)->count();
        $departuresToday = (clone $liveBookings)->where('status', 'checked_in')->whereDate('check_out', $today)->count();
        $overdueDepartures = (clone $liveBookings)->where('status', 'checked_in')->whereDate('check_out', '<', $today)->count();
        $expiringBookings = Booking::query()
            ->with(['property.building', 'invoices.payments'])
            ->whereHas('property')
            ->whereNotIn('status', ['checked_out', 'cancelled'])
            ->whereDoesntHave('renewals', fn ($query) => $query->whereNotIn('status', ['cancelled', 'checked_out']))
            ->get()
            ->each(function (Booking $booking) {
                // Imported/legacy extensions may have a correct paid invoice period while
                // the booking still carries its original checkout date. Use the latest
                // fully-paid stay period so an old checkout warning is not shown.
                $paidExtendedCheckout = $booking->invoices
                    ->whereIn('invoice_type', ['extension', 'renewal'])
                    ->filter(fn (BookingInvoice $invoice) => $invoice->legacy_owner_settled
                        || $invoice->status === 'paid'
                        || (float) $invoice->payments->sum('amount') + 0.01 >= (float) $invoice->total_amount)
                    ->pluck('period_to')->filter()->sortDesc()->first();
                $effectiveCheckout = collect([$booking->check_out, $paidExtendedCheckout])->filter()->sortDesc()->first();
                $booking->setAttribute('follow_up_checkout', $effectiveCheckout);
            })
            ->filter(fn (Booking $booking) => $booking->follow_up_checkout?->lte($today->copy()->addDays(3)))
            ->sortBy(fn (Booking $booking) => $booking->follow_up_checkout?->format('Y-m-d').' '.($booking->check_out_time ?: '11:00'))
            ->values();
        $pendingInvoices = BookingInvoice::query()
            ->with(['booking.property.building'])
            ->withSum('payments', 'amount')
            ->withCount('allPayments')
            ->where('legacy_owner_settled', false)
            // Show three days before the due date and keep every unpaid invoice
            // visible after it becomes overdue until its balance reaches zero.
            // Older extension invoices may not have due_date, so their period
            // start is the payment due date for follow-up purposes.
            ->whereRaw('DATE(COALESCE(due_date, period_from)) <= ?', [$today->copy()->addDays(3)->toDateString()])
            ->whereHas('booking', fn ($query) => $query->where('status', '!=', 'cancelled')->whereHas('property'))
            ->orderByRaw('COALESCE(due_date, period_from) ASC')
            ->get()
            ->filter(fn (BookingInvoice $invoice) => $invoice->balance_due > 0.009)
            ->values();
        $pendingFinancialApprovals = auth()->user()?->hasAnyRole(['Manager', 'Admin', 'Accounting', 'Super Administrator'])
            ? FinancialApprovalRequest::with(['booking', 'invoice', 'requester'])->where('status', 'pending')->latest('requested_at')->limit(8)->get()
            : collect();

        $landlordCount = (int) ($userCounts['landlord'] ?? 0);
        $agentCount = (int) ($userCounts['agent'] ?? 0);
        $tenantCount = (int) ($userCounts['tenant'] ?? 0);
        $maintainerCount = (int) ($userCounts['maintainer'] ?? 0);
        $totalRegisteredUsers = (int) $userCounts->sum();
        $otherUsers = $totalRegisteredUsers - $landlordCount - $agentCount - $tenantCount - $maintainerCount;

        $recentProperties = Property::query()
            ->select('id', 'building_id', 'name', 'status', 'rent', 'created_at')
            ->selectSub($permitExpiry, 'wallet_expiry')
            ->with('building')
            ->latest()
            ->limit(6)
            ->get();

        return view('admin.dashboard.index', compact(
            'totalProperties',
            'landlordCount',
            'agentCount',
            'tenantCount',
            'maintainerCount',
            'totalRegisteredUsers',
            'propertiesRented',
            'propertiesVacant',
            'upcomingDtcmExpiry',
            'recentProperties', 'occupiedUnits', 'emptyUnits', 'occupancyPercent', 'arrivalsToday', 'departuresToday', 'overdueDepartures', 'otherUsers', 'expiringBookings', 'pendingInvoices', 'pendingFinancialApprovals'
        ));
    }
}
