<?php

namespace App\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingInvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\AgentCommission;

class AgentController extends Controller
{
    public function dashboard()
    {
        $bookings = Booking::with('property.building')
            ->where('agent_id', Auth::id())
            ->latest()
            ->get();
        $paidBookings = $bookings->where('invoice_status', 'paid');
        $commissionPercent = (float) (Auth::user()->agent_commission ?? 0);
        $commissionRows = BookingInvoicePayment::with(['invoice.payments', 'invoice.booking.property'])
            ->whereNull('reversed_at')
            ->whereHas('invoice.booking', fn ($query) => $query->where('agent_id', Auth::id()))
            ->latest('payment_date')
            ->get()
            ->map(function (BookingInvoicePayment $payment) use ($commissionPercent) {
                $allocation = $payment->allocation ?? [];
                $agencyFee = AgentCommission::agencyFee($payment);
                if ($agencyFee <= 0) return null;

                $booking = $payment->invoice?->booking;
                $split = AgentCommission::split($agencyFee, $booking, $allocation);
                $rate = $split['rate'];

                return [
                    'payment' => $payment, 'invoice' => $payment->invoice, 'booking' => $booking,
                    'agency_fee' => $agencyFee, 'rate' => $rate,
                    'commission' => $split['agent_share'],
                ];
            })
            ->filter()
            ->values();
        $collectedAgencyFees = $commissionRows->sum('agency_fee');
        $earnedCommission = $commissionRows->sum('commission');

        return view('agent.dashboard.index', compact(
            'bookings', 'paidBookings', 'commissionPercent', 'commissionRows',
            'collectedAgencyFees', 'earnedCommission'
        ));
    }
}
