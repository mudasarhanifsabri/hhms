<div class="pwa-menu-backdrop" data-pwa-menu-close></div>
<aside class="pwa-side-menu" id="pwaSideMenu" aria-hidden="true">
    <div class="pwa-menu-profile">
        <img src="{{ auth()->user()?->profile_photo ? asset('/' . auth()->user()->profile_photo) : asset('assets/images/logo-sm.png') }}" alt="">
        <div>
            <strong>{{ auth()->user()->name ?? 'Maintainer' }}</strong>
            <span>{{ auth()->user()->email ?? '' }}</span>
        </div>
    </div>
    <nav>
        <a href="{{ route('maintainer.task.index') }}"><i class="ri-list-check-2"></i> My Tasks</a>
        <a href="{{ route('maintainer.notifications') }}"><i class="ri-notification-3-line"></i> Notifications</a>
        <a href="{{ route('maintainer.profile') }}"><i class="ri-user-line"></i> Profile</a>
        <button type="button" data-install-pwa><i class="ri-download-cloud-2-line"></i> Install App</button>
    </nav>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit"><i class="ri-logout-box-r-line"></i> Logout</button>
    </form>
</aside>
