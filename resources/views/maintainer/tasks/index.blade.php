@extends('layouts.app')

@section('content')
<div class="pwa-screen pwa-home-screen">
    <header class="pwa-bright-header">
        <button type="button" class="pwa-brand-avatar" data-pwa-menu-open aria-label="Open menu">{{ strtoupper(substr(auth()->user()->name ?? 'M', 0, 1)) }}</button>
        <div><h1>Hello, {{ explode(' ', auth()->user()->name ?? 'Maintainer')[0] }}</h1><p>Your field workspace</p></div>
        <a href="{{ route('maintainer.notifications') }}" class="pwa-header-action" aria-label="Alerts"><i class="ri-notification-3-line"></i></a>
    </header>

    <div class="pwa-content pwa-list-content">
        <section class="pwa-bright-stats" aria-label="Task overview">
            <div><strong>{{ $stats['total'] }}</strong><span>Today</span></div>
            <div><strong>{{ $stats['completed'] }}</strong><span>Done</span></div>
            <div><strong>{{ $stats['in_progress'] }}</strong><span>Active</span></div>
        </section>
        <a href="{{ route('maintainer.inspection.create') }}" class="pwa-create-inspection">
            <i class="ri-checkbox-circle-line"></i><span><strong>Create inspection</strong><small>Choose any unit and start immediately</small></span><i class="ri-arrow-right-s-line"></i>
        </a>
        <h2 class="pwa-section-title">My workspace</h2>
        <div class="pwa-workspace-links">
            <a href="{{ route('maintainer.task.index', ['inspections_only' => 1]) }}"><i class="ri-file-list-3-line"></i><span><strong>My inspections</strong><small>Assigned checklists</small></span><i class="ri-arrow-right-s-line"></i></a>
            <a href="{{ route('maintainer.task.index') }}"><i class="ri-clipboard-line"></i><span><strong>All tasks</strong><small>Maintenance and more</small></span><i class="ri-arrow-right-s-line"></i></a>
        </div>
        <h2 class="pwa-section-title">Recent activity</h2>
        @if(request()->boolean('inspections_only'))
            <h3>My Inspections</h3>
            <p class="text-muted">Office requests and inspections you create yourself.</p>
            <a href="{{ route('maintainer.inspection.create') }}" class="pwa-primary-button purple mb-3"><i class="ri-add-circle-line me-2"></i> New Inspection</a>
        @endif
        <form action="{{ route('maintainer.task.index') }}" method="GET" class="pwa-search-row pwa-compact-filters">
            @if(request()->boolean('inspections_only'))<input type="hidden" name="inspections_only" value="1">@endif
            <div class="pwa-search">
                <i class="ri-search-line"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search tasks...">
            </div>
            <button class="pwa-filter-button" aria-label="Filter"><i class="ri-filter-3-line"></i></button>
        </form>

        <div class="pwa-tabs">
            <a href="{{ route('maintainer.task.index', request()->only('inspections_only')) }}" class="{{ !request('status') ? 'active' : '' }}">All</a>
            <a href="{{ route('maintainer.task.index', array_merge(request()->only('inspections_only'), ['status' => 'assigned'])) }}" class="{{ request('status') === 'assigned' ? 'active' : '' }}">Assigned</a>
            <a href="{{ route('maintainer.task.index', array_merge(request()->only('inspections_only'), ['status' => 'in_progress'])) }}" class="{{ request('status') === 'in_progress' ? 'active' : '' }}">In Progress</a>
            <a href="{{ route('maintainer.task.index', array_merge(request()->only('inspections_only'), ['status' => 'completed'])) }}" class="{{ request('status') === 'completed' ? 'active' : '' }}">Completed</a>
        </div>

        <div class="pwa-task-list">
            @forelse($tasks as $task)
                <a href="{{ route('maintainer.task.show', $task->id) }}" class="pwa-task-card" data-task-card-id="{{ $task->id }}">
                    <div class="pwa-task-card-head">
                        <span class="pwa-chip purple">{{ $task->task_display_number }}</span>
                        <span class="pwa-status-pill">{{ $task->status_label }}</span>
                    </div>
                    <h3>{{ $task->title }}</h3>
                    <p>{{ $task->booking?->property?->building?->name ?? 'Property' }} &bull; {{ $task->booking?->property?->name ?? 'Unit' }}</p>
                    <div class="pwa-task-card-meta">
                        <span><small>Priority</small><strong class="priority-{{ $task->priority }}">{{ $task->priority_label }}</strong></span>
                        <span><small>Due Date</small><strong>{{ $task->due_date?->format('d M, Y') ?? '-' }}</strong></span>
                    </div>
                    <i class="ri-more-2-fill"></i>
                </a>
            @empty
                <div class="pwa-empty">No tasks assigned to you.</div>
            @endforelse
        </div>
    </div>
    @include('maintainer.partials.mobile-nav')
</div>
@endsection
