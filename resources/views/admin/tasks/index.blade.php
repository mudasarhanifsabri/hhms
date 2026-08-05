@extends('layouts.app')

@push('styles')
<style>
    .task-list-table {
        table-layout: fixed;
        width: 100%;
    }

    .task-list-table th,
    .task-list-table td {
        white-space: normal;
        vertical-align: middle;
    }

    .task-list-table .task-col-main { width: 26%; }
    .task-list-table .task-col-unit { width: 24%; }
    .task-list-table .task-col-type { width: 13%; }
    .task-list-table .task-col-staff { width: 15%; }
    .task-list-table .task-col-status { width: 14%; }
    .task-list-table .task-col-action { width: 8%; }

    .task-list-title {
        display: block;
        line-height: 1.25;
        max-width: 100%;
        overflow-wrap: anywhere;
    }

    .task-list-muted {
        color: #667085;
        display: block;
        font-size: 12px;
        line-height: 1.35;
        margin-top: 3px;
    }

    .task-location-lines span {
        display: block;
        line-height: 1.35;
    }

    .task-location-lines .unit-name {
        color: #111827;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .task-location-lines .building-name,
    .task-location-lines .sub-unit {
        color: #667085;
        font-size: 12px;
    }

    .task-list-progress {
        min-width: 0;
        width: 100%;
    }

    @media (max-width: 1199.98px) {
        .task-list-table .task-col-main { width: 28%; }
        .task-list-table .task-col-unit { width: 25%; }
        .task-list-table .task-col-type { width: 12%; }
        .task-list-table .task-col-staff { width: 15%; }
        .task-list-table .task-col-status { width: 13%; }
        .task-list-table .task-col-action { width: 7%; }
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Total Tasks</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $totalTasks }}</p></div>
            <div class="avatar-md bg-primary bg-opacity-10 rounded"><iconify-icon icon="solar:clipboard-list-bold" class="fs-32 text-primary avatar-title"></iconify-icon></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Open Tasks</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $openTasks }}</p></div>
            <div class="avatar-md bg-warning bg-opacity-10 rounded"><iconify-icon icon="solar:clock-circle-bold" class="fs-32 text-warning avatar-title"></iconify-icon></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">In Progress</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $inProgressTasks }}</p></div>
            <div class="avatar-md bg-info bg-opacity-10 rounded"><iconify-icon icon="solar:hourglass-line-duotone" class="fs-32 text-info avatar-title"></iconify-icon></div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
            <div><h4 class="card-title mb-2">Overdue</h4><p class="text-muted fw-medium fs-22 mb-0">{{ $overdueTasks }}</p></div>
            <div class="avatar-md bg-danger bg-opacity-10 rounded"><iconify-icon icon="solar:danger-triangle-bold" class="fs-32 text-danger avatar-title"></iconify-icon></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h4 class="card-title mb-0">Task Manager</h4>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                    <iconify-icon icon="solar:add-circle-bold" class="align-middle fs-18"></iconify-icon> Create Task
                </button>
                <a href="{{ route('admin.task.grid', request()->query()) }}" class="btn btn-sm btn-outline-light">Grid View</a>
            </div>
        </div>
        <form action="{{ route('admin.task.index') }}" method="GET" class="row g-2">
            <div class="col-lg-3">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search task no, title, description">
            </div>
            <div class="col-lg-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach(['pending' => 'Pending', 'accepted' => 'Accepted', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach(\App\Models\BookingTask::TYPES as $key => $label)
                        <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select name="assigned_to" class="form-select form-select-sm">
                    <option value="">All Staff</option>
                    @foreach($maintainers as $maintainer)
                        <option value="{{ $maintainer->id }}" @selected(request('assigned_to') === $maintainer->id)>{{ $maintainer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
                <button class="btn btn-sm btn-primary flex-fill">Filter</button>
                <a href="{{ route('admin.task.index') }}" class="btn btn-sm btn-outline-light">Reset</a>
            </div>
        </form>
    </div>
    <div class="table-responsive overflow-visible">
        <table class="table align-middle table-hover table-centered mb-0 task-list-table">
            <thead class="bg-light-subtle">
                <tr>
                    <th class="task-col-main">Task</th>
                    <th class="task-col-unit">Unit / Building</th>
                    <th class="task-col-type">Category</th>
                    <th class="task-col-staff">Staff / Due</th>
                    <th class="task-col-status">Status</th>
                    <th class="task-col-action text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    @php
                        $taskProperty = $task->booking?->property ?: $task->property;
                        $taskBuilding = $taskProperty?->building;
                        $buildingName = $taskBuilding?->building_name ?: $taskBuilding?->name;
                        $subUnitDetails = collect([
                            $taskProperty?->community,
                            $taskProperty?->unit_floor_label ?: ($taskProperty?->floor ? 'Floor ' . $taskProperty->floor : null),
                            $taskProperty?->room_no ? 'Room ' . $taskProperty->room_no : null,
                        ])->filter()->implode(' | ');
                    @endphp
                    <tr>
                        <td>
                            <span class="fw-semibold text-primary">{{ $task->task_display_number }}</span>
                            <a href="{{ route('admin.task.show', $task->id) }}" class="text-dark fw-medium task-list-title">{{ $task->title }}</a>
                            @if($task->booking)
                                <span class="task-list-muted">Booking: {{ $task->booking->booking_reference }}</span>
                            @endif
                            <span class="task-list-muted">Created: {{ $task->created_at?->format('d M Y') }} by {{ $task->createdBy?->name ?? 'System' }}</span>
                        </td>
                        <td>
                            <div class="task-location-lines">
                                <span class="unit-name">{{ $taskProperty?->name ?? '-' }}</span>
                                <span class="building-name">{{ $buildingName ?: 'No Building' }}</span>
                                @if($subUnitDetails)
                                    <span class="sub-unit">{{ $subUnitDetails }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $task->type_label }}</div>
                            <span class="badge bg-light-subtle text-dark border mt-1">{{ $task->priority_label }}</span>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $task->assignedUser?->name ?? 'Not assigned' }}</div>
                            <span class="task-list-muted">Due: {{ $task->due_date?->format('d M Y') ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $task->status_class }} text-white">{{ $task->status_label }}</span>
                            <div class="progress task-list-progress mt-2" style="height: 6px;">
                                <div class="progress-bar" style="width: {{ (int) $task->progress }}%"></div>
                            </div>
                            <span class="small text-muted">{{ (int) $task->progress }}%</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.task.show', $task->id) }}" class="btn btn-light btn-sm" title="Task Details" aria-label="Task Details">
                                <iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No tasks found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $tasks->links('pagination::bootstrap-5') }}</div>
</div>

<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="{{ route('admin.task.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="_create_task" value="1">
            <div class="modal-header">
                <h5 class="modal-title" id="createTaskModalLabel">Create Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($errors->any() && old('_create_task'))
                    <div class="alert alert-danger">Please check the task form and try again.</div>
                @endif
                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">Booking</label>
                        <select name="booking_id" class="form-select">
                            <option value="">No booking / property task</option>
                            @foreach($bookings as $booking)
                                <option value="{{ $booking->id }}" @selected(old('booking_id') === $booking->id)>
                                    {{ $booking->booking_reference }} - {{ $booking->property?->name ?? 'Unit' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">Property / Unit <span class="text-danger">*</span></label>
                        <select name="property_id" class="form-select">
                            <option value="">Select unit</option>
                            @foreach($properties as $property)
                                <option value="{{ $property->id }}" @selected(old('property_id') === $property->id)>
                                    {{ $property->building?->name ? $property->building->name . ' - ' : '' }}{{ $property->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">If booking is selected, its unit will be used automatically.</small>
                    </div>
                    <div class="col-lg-8">
                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" class="form-control" placeholder="AC not cooling in Unit 1203" required>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach(\App\Models\BookingTask::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', 'maintenance') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Priority <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            @foreach(\App\Models\BookingTask::PRIORITIES as $key => $label)
                                <option value="{{ $key }}" @selected(old('priority', 'medium') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Assigned Staff</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">Assign later</option>
                            @foreach($maintainers as $maintainer)
                                <option value="{{ $maintainer->id }}" @selected(old('assigned_to') === $maintainer->id)>{{ $maintainer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-control">
                    </div>
                    <div class="col-lg-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Explain the work and any access notes.">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-lg-12">
                        <label class="form-label">Task Pictures</label>
                        <input type="file" name="pictures[]" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple>
                        <small class="text-muted">Images are compressed and stored as optimized WebP where possible.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Task</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
@if($errors->any() && old('_create_task'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('createTaskModal'));
        modal.show();
    });
</script>
@endif
@endpush
