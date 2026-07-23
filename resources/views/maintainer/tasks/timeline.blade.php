@extends('layouts.app')

@section('content')
<div class="pwa-screen">
    @include('maintainer.partials.pwa-header', ['title' => 'Timeline', 'back' => route('maintainer.task.show', $task->id)])

    <div class="pwa-content">
        <div class="pwa-timeline">
            @forelse($task->activities as $activity)
                <div class="pwa-timeline-item">
                    <span class="pwa-timeline-dot"><i class="ri-checkbox-circle-line"></i></span>
                    <div>
                        <h3>{{ $activity->action }}</h3>
                        <p>{{ $activity->user?->name ?? 'System' }}</p>
                        @if($activity->comment)<p>{{ $activity->comment }}</p>@endif
                        <small>{{ $activity->created_at?->format('d M Y • h:i A') }}</small>
                    </div>
                </div>
            @empty
                <div class="pwa-empty">No timeline activity yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
