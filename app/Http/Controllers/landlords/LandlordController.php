<?php

namespace App\Http\Controllers\Landlords;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LandlordController extends Controller
{
    public function dashboard()
    {
        $properties = Property::with('building')->where('landlord_id', Auth::id())->latest()->get();
        $propertyIds = $properties->pluck('id');
        $bookings = Booking::with('property')->whereIn('property_id', $propertyIds)->latest()->take(8)->get();
        $entries = LandlordAccountEntry::where('landlord_id', Auth::id())->latest('entry_date')->take(8)->get();
        $balance = (float) ($entries->first()?->balance_after ?? LandlordAccountEntry::where('landlord_id', Auth::id())->latest('entry_date')->value('balance_after') ?? 0);

        return view('landlord.dashboard.index', compact('properties', 'bookings', 'entries', 'balance'));
    }
}
