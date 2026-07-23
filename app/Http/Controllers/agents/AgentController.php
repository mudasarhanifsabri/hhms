<?php

namespace App\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $estimatedCommission = round($paidBookings->sum('rent_amount') * ($commissionPercent / 100), 2);

        return view('agent.dashboard.index', compact('bookings', 'paidBookings', 'commissionPercent', 'estimatedCommission'));
    }
}
