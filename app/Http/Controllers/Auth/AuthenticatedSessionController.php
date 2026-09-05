<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirect to the correct dashboard based on user role
        // This is a more readable way to handle the redirection based on user role
        return match (Auth::user()->role) {
            'admin'      => redirect()->route('admin.dashboard'),
            'tenant'     => redirect()->route('tenant.dashboard'),
            'landlord'   => $this->isMobile($request)
                ? redirect()->route('landlord.app')
                : redirect()->route('landlord.dashboard'),
            'agent'      => redirect()->route('agent.dashboard'),
            'maintainer' => redirect()->route('maintainer.dashboard'),
            default      => abort(403, 'Unauthorized')
        };

    }

    private function isMobile(Request $request): bool
    {
        return (bool) preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', (string) $request->userAgent());
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
