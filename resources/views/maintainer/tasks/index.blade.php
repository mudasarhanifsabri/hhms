@extends('layouts.app')

@section('content')
<div class="pwa-screen pwa-home-screen">
    <div class="pwa-hero">
        @include('maintainer.partials.pwa-header', ['menu' => true, 'light' => true])
        <div class="pwa-greeting">
            <p>Good Morning</p>
            <h2>{{ auth()->user()->name ?? 'Maintainer' }} <span>&#128075;</span></h2>
        </div>
        <div class="pwa-stats-card">
            <div class="pwa-stat">
                <span class="pwa-stat-icon blue"><i class="ri-tools-line"></i></span>
                <strong>{{ $stats['total'] }}</strong>
                <small>My Tasks</small>
            </div>
            <div class="pwa-stat">
                <span class="pwa-stat-icon orange"><i class="ri-calendar-check-line"></i></span>
                <strong>{{ $stats['in_progress'] }}</strong>
                <small>In Progress</small>
            </div>
            <div class="pwa-stat">
                <span class="pwa-stat-icon green"><i class="ri-check-line"></i></span>
                <strong>{{ $stats['completed'] }}</strong>
                <small>Completed</small>
            </div>
            <div class="pwa-stat">
                <span class="pwa-stat-icon red"><i class="ri-time-line"></i></span>
                <strong>{{ $stats['overdue'] }}</strong>
                <small>Overdue</small>
            </div>
        </div>
    </div>

    <div class="pwa-content pwa-list-content">
        <form action="{{ route('maintainer.task.index') }}" method="GET" class="pwa-search-row">
            <div class="pwa-search">
                <i class="ri-search-line"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search tasks...">
            </div>
            <button class="pwa-filter-button" aria-label="Filter"><i class="ri-filter-3-line"></i></button>
        </form>

        <div class="pwa-tabs">
            <a href="{{ route('maintainer.task.index') }}" class="{{ !request('status') ? 'active' : '' }}">All</a>
            <a href="{{ route('maintainer.task.index', ['status' => 'assigned']) }}" class="{{ request('status') === 'assigned' ? 'active' : '' }}">Assigned</a>
            <a href="{{ route('maintainer.task.index', ['status' => 'in_progress']) }}" class="{{ request('status') === 'in_progress' ? 'active' : '' }}">In Progress</a>
            <a href="{{ route('maintainer.task.index', ['status' => 'completed']) }}" class="{{ request('status') === 'completed' ? 'active' : '' }}">Completed</a>
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
