@extends('layouts.app')

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">My Task Grid</h4>
        <a href="{{ route('maintainer.task.index') }}" class="btn btn-sm btn-outline-light">List View</a>
    </div>
</div>

<div class="row">
    @forelse($tasks as $task)
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <a href="{{ route('maintainer.task.show', $task->id) }}" class="text-dark fw-medium fs-16">{{ $task->task_display_number }}</a>
                            <h5 class="mb-1 mt-1">{{ $task->title }}</h5>
                            <p class="text-muted mb-0">{{ $task->type_label }}</p>
                        </div>
                        <span class="badge {{ $task->status_class }} text-white">{{ $task->status_label }}</span>
                    </div>
                    <div class="mt-3">
                        <p class="mb-1"><span class="text-muted">Booking:</span> {{ $task->booking?->booking_reference ?? '-' }}</p>
                        <p class="mb-1"><span class="text-muted">Unit:</span> {{ $task->booking?->property?->name ?? '-' }}</p>
                        <p class="mb-0"><span class="text-muted">Cost:</span> {{ number_format((float) $task->total_cost, 2) }} AED</p>
                    </div>
                </div>
                <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center">
                    <p class="fw-medium text-dark fs-16 mb-0">{{ (int) $task->progress }}%</p>
                    <a href="{{ route('maintainer.task.show', $task->id) }}" class="link-primary fw-medium">Details <i class="ri-arrow-right-line align-middle"></i></a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-4">No tasks assigned to you.</div></div></div>
    @endforelse
</div>

{{ $tasks->links('pagination::bootstrap-5') }}
@include('maintainer.partials.mobile-nav')
@endsection
