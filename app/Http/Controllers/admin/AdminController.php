<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
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
            ->whereIn('role', ['landlord', 'agent', 'tenant', 'maintainer'])
            ->groupBy('role')
            ->pluck('total', 'role');

        $propertyStats = Property::query()
            ->selectRaw("
                COUNT(*) as total_properties,
                SUM(CASE WHEN status = 'rented' THEN 1 ELSE 0 END) as rented_properties,
                SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) as vacant_properties,
                SUM(CASE WHEN dtcm_permit_expiry BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_dtcm
            ", [$today->toDateString(), $in30->toDateString()])
            ->first();

        $totalProperties = (int) ($propertyStats->total_properties ?? 0);
        $propertiesRented = (int) ($propertyStats->rented_properties ?? 0);
        $propertiesVacant = (int) ($propertyStats->vacant_properties ?? 0);
        $upcomingDtcmExpiry = (int) ($propertyStats->expiring_dtcm ?? 0);

        $landlordCount = (int) ($userCounts['landlord'] ?? 0);
        $agentCount = (int) ($userCounts['agent'] ?? 0);
        $tenantCount = (int) ($userCounts['tenant'] ?? 0);
        $maintainerCount = (int) ($userCounts['maintainer'] ?? 0);
        $totalRegisteredUsers = $landlordCount + $agentCount + $tenantCount + $maintainerCount;

        $recentProperties = Property::query()
            ->select('id', 'name', 'status', 'rent', 'dtcm_permit_expiry', 'created_at')
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
            'recentProperties'
        ));
    }
}
