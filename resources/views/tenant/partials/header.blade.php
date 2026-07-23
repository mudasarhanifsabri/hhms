@props(['title' => 'Booking Details', 'back' => route('tenant.dashboard')])

<div class="tenant-topbar">
    <a href="{{ $back }}" aria-label="Back"><i class="ri-arrow-left-line"></i></a>
    <h1>{{ $title }}</h1>
    <span></span>
</div>
