@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Notifications', 'back' => route('maintainer.task.index')])
    <div class="pwa-content">
        <div class="pwa-alert">
            <i class="ri-notification-3-line"></i>
            <p>Enable browser notifications to get alerts for newly assigned jobs on this device.</p>
        </div>
        <button type="button" class="pwa-primary-button purple" data-enable-notifications>Enable Notifications</button>
        <p class="pwa-notification-state" data-notification-state></p>

        <section class="pwa-section">
            <h3>New Jobs</h3>
            <div class="pwa-task-list">
                @forelse($tasks as $task)
                    <a href="{{ route('maintainer.task.show', $task->id) }}" class="pwa-notification-row">
                        <span><i class="ri-tools-line"></i></span>
                        <div>
                            <strong>{{ $task->title }}</strong>
                            <p>{{ $task->task_display_number }} • {{ $task->booking?->property?->name ?? 'Unit' }}</p>
                        </div>
                        <small>{{ $task->created_at?->diffForHumans() }}</small>
                    </a>
                @empty
                    <div class="pwa-empty">No new task notifications.</div>
                @endforelse
            </div>
        </section>
    </div>
    @include('maintainer.partials.mobile-nav')
</div>
@endsection
