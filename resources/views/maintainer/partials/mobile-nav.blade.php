<div class="maintainer-bottom-nav d-md-none">
    @include('maintainer.partials.side-menu')
    <a href="{{ route('maintainer.task.index') }}" class="{{ request()->routeIs('maintainer.dashboard') ? 'active' : '' }}">
        <i class="ri-home-5-fill"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('maintainer.task.index') }}" class="{{ request()->routeIs('maintainer.task.*') ? 'active' : '' }}">
        <i class="ri-list-check-2"></i>
        <span>Tasks</span>
    </a>
    <a href="{{ route('maintainer.task.index', ['inspections_only' => 1]) }}" class="center">
        <i class="ri-clipboard-line"></i>
        <span>Inspect</span>
    </a>
    <a href="{{ route('maintainer.notifications') }}" class="{{ request()->routeIs('maintainer.notifications') ? 'active' : '' }}">
        <i class="ri-notification-3-line"></i>
        <span>Notifications</span>
    </a>
    <a href="{{ route('maintainer.profile') }}" class="{{ request()->routeIs('maintainer.profile') ? 'active' : '' }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</div>
<div class="maintainer-bottom-spacer d-md-none"></div>
