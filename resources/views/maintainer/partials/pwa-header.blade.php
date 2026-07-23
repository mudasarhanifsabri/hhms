@props(['title' => null, 'back' => null, 'menu' => false, 'light' => false])

<div class="pwa-page-header {{ $light ? 'is-light' : '' }}">
    @if($back)
        <a href="{{ $back }}" class="pwa-icon-button" aria-label="Back"><i class="ri-arrow-left-line"></i></a>
    @elseif($menu)
        <button type="button" class="pwa-icon-button" data-pwa-menu-open aria-label="Menu"><i class="ri-menu-line"></i></button>
    @else
        <span></span>
    @endif
    @if($title)
        <h1>{{ $title }}</h1>
    @endif
    @if($menu)
        <a href="{{ route('maintainer.notifications') }}" class="pwa-icon-button pwa-bell" aria-label="Notifications"><i class="ri-notification-3-line"></i></a>
    @else
        <button type="button" class="pwa-icon-button" aria-label="More"><i class="ri-more-fill"></i></button>
    @endif
</div>
