@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Task Details', 'back' => route('maintainer.task.index')])

    <div class="pwa-content">
        <div class="pwa-detail-head">
            <span class="pwa-chip purple">{{ $task->task_display_number }}</span>
            <span class="pwa-priority high">{{ $task->priority_label }}</span>
        </div>
        <h2 class="pwa-title">{{ $task->title }}</h2>
        <p class="pwa-subtitle">{{ $task->booking?->property?->building?->name ?? 'Property' }} • {{ $task->booking?->property?->name ?? 'Unit' }}</p>

        <div class="pwa-info-grid">
            <span>Category</span><strong>{{ $task->type_label }}</strong>
            <span>Assigned By</span><strong>{{ $task->createdBy?->name ?? 'Admin User' }}</strong>
            <span>Assigned To</span><strong>{{ $task->assignedUser?->name ?? auth()->user()->name }}</strong>
            <span>Due Date</span><strong>{{ $task->due_date?->format('d M, Y') ?? '-' }}</strong>
            <span>Status</span><strong><em>{{ $task->status_label }}</em></strong>
            <span>Estimated Cost</span><strong>AED {{ number_format((float) $task->total_cost, 2) }}</strong>
        </div>

        <section class="pwa-section">
            <h3>Description</h3>
            <p>{{ $task->description ?: 'Please check and update this task as soon as possible.' }}</p>
        </section>

        <section class="pwa-section">
            <h3>Photos ({{ count((array) $task->pictures) }})</h3>
            <div class="pwa-photo-row">
                @forelse((array) $task->pictures as $picture)
                    <a href="{{ asset($picture) }}" target="_blank" class="pwa-photo-thumb real" style="background-image:url('{{ asset($picture) }}')"></a>
                @empty
                    <div class="pwa-photo-thumb"></div>
                    <div class="pwa-photo-thumb is-alt"></div>
                @endforelse
            </div>
        </section>

        <div class="pwa-action-stack">
            @if(in_array($task->status, ['new', 'open', 'assigned'], true))
                <a href="{{ route('maintainer.task.accept.form', $task->id) }}" class="pwa-primary-button green">Accept Task</a>
            @endif
            <a href="{{ route('maintainer.task.remark.form', $task->id) }}" class="pwa-secondary-button">Add Remark</a>
            <a href="{{ route('maintainer.task.timeline', $task->id) }}" class="pwa-secondary-button">Timeline</a>
            <a href="{{ route('maintainer.task.cost.form', $task->id) }}" class="pwa-secondary-button">Add Cost</a>
            @if(! in_array($task->status, ['completed', 'closed', 'cancelled'], true))
                <a href="{{ route('maintainer.task.complete.form', $task->id) }}" class="pwa-primary-button green">Complete Task</a>
            @endif
        </div>
    </div>
    @include('maintainer.partials.mobile-nav')
</div>
@endsection
