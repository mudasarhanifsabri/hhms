<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompleteTenantProfile
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->tenant_profile_required && ! $request->routeIs('tenant.profile.*')) {
            return redirect()->route('tenant.profile.edit');
        }

        return $next($request);
    }
}
