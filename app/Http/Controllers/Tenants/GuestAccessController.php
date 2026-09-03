<?php

namespace App\Http\Controllers\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Support\BookingTenantProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Mpdf\QrCode\Output\Svg;
use Mpdf\QrCode\QrCode;

class GuestAccessController extends Controller
{
    public function show(Property $property)
    {
        // A public unit QR is only an entry point, never a booking credential.
        return view('tenant.access', ['unitId' => $property->id]);
    }

    public function activate(Request $request, Property $property)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'booking_reference' => 'required|string|max:100']);
        $booking = Booking::where('property_id', $property->id)->where('booking_reference', trim($data['booking_reference']))
            ->whereRaw('LOWER(guest_email) = ?', [strtolower(trim($data['email']))])
            ->whereIn('status', ['confirmed', 'checked_in'])->first();
        if ($booking) {
            $tenant = BookingTenantProfile::sync($booking);
            // Existing explicit links may differ from the original booking email.
            if ($tenant && $tenant->role === 'tenant' && strcasecmp($tenant->email, trim($data['email'])) === 0) {
                Password::sendResetLink(['email' => $tenant->email]);
            }
        }

        return back()->with('status', 'If these details match an active booking, a secure password setup link has been sent to your booking email. Check your inbox and spam folder. If it does not arrive, contact management.');
    }

    public function poster(Property $property)
    {
        $property->load('building');
        $url = route('guest.access', $property);
        $qr = (new Svg)->output(new QrCode($url), 320);

        return view('admin.properties.guest-qr', compact('property', 'url', 'qr'));
    }
}
