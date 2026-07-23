@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Profile', 'back' => route('maintainer.task.index')])
    <div class="pwa-content">
        <div class="pwa-profile-card">
            <img src="{{ $user->profile_photo ? asset('/' . $user->profile_photo) : asset('assets/images/logo-sm.png') }}" alt="">
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>
            <span>{{ ucfirst($user->role) }}</span>
        </div>

        <div class="pwa-stats-flat">
            <div><strong>{{ $stats['total'] }}</strong><span>Total Tasks</span></div>
            <div><strong>{{ $stats['in_progress'] }}</strong><span>In Progress</span></div>
            <div><strong>{{ $stats['completed'] }}</strong><span>Completed</span></div>
        </div>

        <div class="pwa-settings-list">
            <a href="{{ route('maintainer.notifications') }}"><i class="ri-notification-3-line"></i><span>Notifications</span><b>Manage</b></a>
            <a href="{{ route('maintainer.task.index') }}"><i class="ri-list-check-2"></i><span>My Tasks</span><b>Open</b></a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"><i class="ri-logout-box-r-line"></i><span>Logout</span><b></b></button>
            </form>
        </div>
    </div>
    @include('maintainer.partials.mobile-nav')
</div>
@endsection
