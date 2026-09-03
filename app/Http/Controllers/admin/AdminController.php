<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;
use App\Models\UnitDocument;
use App\Models\User;
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
        $occupiedUnits = (clone $liveBookings)->where('status', 'checked_in')->distinct()->count('property_id');
        $occupancyPercent = $totalProperties > 0 ? round($occupiedUnits / $totalProperties * 100) : 0;
        $arrivalsToday = (clone $liveBookings)->where('status', 'confirmed')->whereDate('check_in', $today)->count();
        $departuresToday = (clone $liveBookings)->where('status', 'checked_in')->whereDate('check_out', $today)->count();
        $overdueDepartures = (clone $liveBookings)->where('status', 'checked_in')->whereDate('check_out', '<', $today)->count();

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
            'recentProperties', 'occupiedUnits', 'occupancyPercent', 'arrivalsToday', 'departuresToday', 'overdueDepartures', 'otherUsers'
        ));
    }
}
