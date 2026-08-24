<div class="wrapper">
    <header>
        <div class="topbar">
            <div class="container-fluid">
                <div class="navbar-header">
                    <div class="d-flex align-items-center gap-2">
                        <div class="topbar-item">
                            <button type="button" class="button-toggle-menu topbar-button" aria-label="Toggle menu">
                                <i class="ri-menu-2-line fs-24"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        @if(auth()->user()?->role === 'admin')
                        @php($adminNotifications = auth()->user()->notifications()->latest()->limit(8)->get())
                        <div class="dropdown topbar-item">
                            <button type="button" class="topbar-button position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                                <i class="ri-notification-3-line fs-24"></i>
                                @if(auth()->user()->unreadNotifications()->exists())<span class="position-absolute top-0 end-0 badge rounded-pill bg-danger">{{ min(99,auth()->user()->unreadNotifications()->count()) }}</span>@endif
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0" style="width:360px;max-width:90vw">
                                <div class="p-3 border-bottom"><h6 class="mb-0">Notifications</h6></div>
                                <div class="p-2">@forelse($adminNotifications as $notification)<a href="{{ data_get($notification->data,'url','#!') }}" class="dropdown-item text-wrap border-bottom py-2"><strong class="d-block">{{ data_get($notification->data,'title','System notification') }}</strong><small class="text-muted">{{ data_get($notification->data,'message') }}</small></a>@empty<div class="text-muted text-center p-3">No notifications</div>@endforelse</div>
                            </div>
                        </div>
                        @endif
                        <div class="topbar-item">
                            <button type="button" class="topbar-button" id="light-dark-mode" aria-label="Toggle color mode">
                                <i class="ri-moon-line fs-24 light-mode"></i>
                                <i class="ri-sun-line fs-24 dark-mode"></i>
                            </button>
                        </div>

                        <div class="dropdown topbar-item d-none d-lg-flex">
                            <button type="button" class="topbar-button" data-toggle="fullscreen" aria-label="Toggle fullscreen">
                                <i class="ri-fullscreen-line fs-24 fullscreen"></i>
                                <i class="ri-fullscreen-exit-line fs-24 quit-fullscreen"></i>
                            </button>
                        </div>

                        <div class="dropdown topbar-item">
                            <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-flex align-items-center">
                                    <img class="rounded-circle" width="32" src="{{ auth()->check() && auth()->user()->profile_photo ? \App\Support\MediaStorage::url(auth()->user()->profile_photo) : asset('default-avatar.png') }}" alt="User Avatar">
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <h6 class="dropdown-header">Welcome {{ auth()->user()->name }}!</h6>

                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <iconify-icon icon="solar:user-broken" class="align-middle me-2 fs-18"></iconify-icon>
                                    <span class="align-middle">Profile</span>
                                </a>

                                <a class="dropdown-item" href="{{ route('password.confirm') }}">
                                    <iconify-icon icon="solar:lock-keyhole-broken" class="align-middle me-2 fs-18"></iconify-icon>
                                    <span class="align-middle">Lock screen</span>
                                </a>

                                <div class="dropdown-divider my-1"></div>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <iconify-icon icon="solar:logout-3-broken" class="align-middle me-2 fs-18"></iconify-icon>
                                        <span class="align-middle">Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
