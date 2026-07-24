<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <base href="{{ url('/') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $portalTitle ?? config('app.name', 'HHMS Portal') }}</title>
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/vendor.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/css/role-portals.css') }}" rel="stylesheet" type="text/css">
    @stack('styles')
</head>
<body class="role-portal">
    @php
        $role = auth()->user()?->role ?? 'guest';
        $homeRoute = match ($role) {
            'landlord' => route('landlord.dashboard'),
            'agent' => route('agent.dashboard'),
            'tenant' => route('tenant.dashboard'),
            default => url('/'),
        };
    @endphp
    <div class="portal-shell">
        <aside class="portal-sidebar">
            <a href="{{ $homeRoute }}" class="portal-brand">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="">
                <span>{{ ucfirst($role) }} Portal</span>
            </a>
            <nav>
                <a href="{{ $homeRoute }}" class="active"><i class="ri-dashboard-2-line"></i> Dashboard</a>
                @if($role === 'landlord')
                    <a href="{{ route('landlord.dashboard') }}#properties"><i class="ri-community-line"></i> Units</a>
                    <a href="{{ route('landlord.dashboard') }}#statement"><i class="ri-file-list-3-line"></i> Statement</a>
                @elseif($role === 'agent')
                    <a href="{{ route('agent.dashboard') }}#bookings"><i class="ri-calendar-check-line"></i> Bookings</a>
                    <a href="{{ route('agent.dashboard') }}#commission"><i class="ri-percent-line"></i> Commission</a>
                @elseif($role === 'tenant')
                    <a href="{{ route('tenant.dashboard') }}#stays"><i class="ri-hotel-bed-line"></i> Stays</a>
                    <a href="{{ route('tenant.dashboard') }}#documents"><i class="ri-file-copy-2-line"></i> Documents</a>
                @endif
            </nav>
            @auth
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"><i class="ri-logout-box-r-line"></i> Logout</button>
                </form>
            @endauth
        </aside>
        <main class="portal-main">
            <header class="portal-topbar">
                <div>
                    <p>{{ $portalEyebrow ?? ucfirst($role) }}</p>
                    <h1>{{ $portalHeading ?? 'Dashboard' }}</h1>
                </div>
                @auth
                    <div class="portal-user">
                        <span>{{ auth()->user()->name }}</span>
                        <img src="{{ auth()->user()->profile_photo ? asset('/' . auth()->user()->profile_photo) : asset('assets/images/logo-sm.png') }}" alt="">
                    </div>
                @endauth
            </header>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
    <script src="assets/js/vendor.js"></script>
    <script src="assets/js/app.js"></script>
    @stack('scripts')
</body>
</html>
