<nav class="tenant-bottom-nav">
    <a href="{{ route('tenant.dashboard') }}" class="{{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"><i class="ri-home-5-fill"></i><span>Home</span></a>
    <a href="{{ route('tenant.maintenance.index') }}" class="{{ request()->routeIs('tenant.maintenance.*') ? 'active' : '' }}"><i class="ri-tools-line"></i><span>Maintenance</span></a>
    <a href="{{ route('tenant.dashboard') }}#bookings"><i class="ri-calendar-check-line"></i><span>Bookings</span></a>
    <a href="{{ route('tenant.dashboard') }}#documents"><i class="ri-file-copy-2-line"></i><span>Docs</span></a>
    <a href="{{ route('tenant.profile.edit') }}"><i class="ri-user-line"></i><span>Profile</span></a>
</nav>
