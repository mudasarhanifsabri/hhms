<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    private const ROUTE_MODULES = [
        'dashboard' => 'dashboard', 'property' => 'units', 'building' => 'units',
        'landlord' => 'owners', 'tenant' => 'tenants', 'booking' => 'bookings', 'booking-invoice' => 'bookings',
        'booking-payment' => 'bookings', 'deposit' => 'bookings',
        'task' => 'tasks', 'inspection' => 'inspections', 'inventory' => 'inspections',
        'accounting' => 'accounting', 'agent' => 'agents', 'maintainer' => 'maintainers',
        'settings' => 'administration', 'software-update' => 'administration',
        'access-control' => 'administration', 'document-ocr' => 'administration',
        'smartlocks' => 'administration',
        'financial-approvals' => 'accounting',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== 'admin') abort(403, 'Unauthorized action.');
        if ($user->hasAnyRole(['Super Administrator', 'Backend IT'])) return $next($request);

        $routeName = (string) $request->route()?->getName();
        $segment = explode('.', str_replace('admin.', '', $routeName))[0] ?? '';
        $module = self::ROUTE_MODULES[$segment] ?? 'administration';
        if (in_array($routeName, ['admin.smartlocks.issue', 'admin.smartlocks.revoke'], true)) $module = 'bookings';
        $action = in_array($request->method(), ['GET', 'HEAD'], true) ? 'view' : 'manage';
        abort_unless($user->can($module.'.'.$action) || ($action === 'view' && $user->can($module.'.manage')), 403,
            'You do not have permission to access this module.');

        return $next($request);
    }
}
