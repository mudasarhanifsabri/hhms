@php
    $mobileItems = [
        ['label' => 'Home', 'icon' => 'ri-home-5-line', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard') || request()->routeIs('admin.dashboard')],
        ['label' => 'Bookings', 'icon' => 'ri-calendar-check-line', 'route' => 'admin.booking.index', 'active' => request()->routeIs('admin.booking.*')],
        ['label' => 'Owners', 'icon' => 'ri-user-star-line', 'route' => 'admin.landlord.index', 'active' => request()->routeIs('admin.landlord.*')],
        ['label' => 'Finance', 'icon' => 'ri-wallet-3-line', 'route' => 'admin.accounting.dashboard', 'active' => request()->routeIs('admin.accounting.*') || request()->routeIs('admin.financial-approvals.*')],
    ];
@endphp
<nav class="admin-mobile-nav" aria-label="Admin mobile navigation">
    @foreach($mobileItems as $item)
        <a href="{{ route($item['route']) }}" @class(['active' => $item['active']])>
            <i class="{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
        </a>
    @endforeach
    <button type="button" data-admin-more aria-label="Open all admin modules">
        <i class="ri-grid-fill"></i><span>More</span>
    </button>
</nav>
