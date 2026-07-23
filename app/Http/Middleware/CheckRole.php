<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
// This middleware checks if the authenticated user has the specified role.
// It can be used to restrict access to certain routes based on user roles.
// For example, you can use it to restrict access to admin routes or tenant routes.

{
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (!$request->user() || $request->user()->role !== $role) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
