@extends('layouts.app')

@section('content')
<div class="card"><div class="card-body"><h5>Staff expense / payment requests</h5><p class="text-muted">Review supplier invoices and staff payment proof before approving. Unpaid requests need office payment; staff-paid requests need reimbursement review.</p><a class="btn btn-outline-primary btn-sm" href="{{ route('admin.accounting.expenses',['task_id'=>$task->id]) }}">Review linked expenses & payment account</a></div></div>
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-1">{{ $task->task_display_number }}</h4>
                    <p class="text-muted mb-0">{{ $task->title }}</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge {{ $task->status_class }} text-white">{{ $task->status_label }}</span>
                    <a href="{{ route('admin.task.index') }}" class="btn btn-sm btn-outline-light">List</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6"><strong>Task Number:</strong> {{ $task->task_display_number }}</div>
                    <div class="col-lg-6"><strong>Booking:</strong> {{ $task->booking?->booking_reference ?? 'Property task' }}</div>
                    <div class="col-lg-6"><strong>Property:</strong> {{ $task->booking?->property?->building?->name ?? $task->property?->building?->name ?? '-' }}</div>
                    <div class="col-lg-6"><strong>Building:</strong> {{ $task->booking?->property?->building?->name ?? $task->property?->building?->name ?? '-' }}</div>
                    <div class="col-lg-6"><strong>Unit:</strong> {{ $task->booking?->property?->name ?? $task->property?->name ?? '-' }}</div>
                    <div class="col-lg-6"><strong>Category:</strong> {{ $task->type_label }}</div>
                    <div class="col-lg-6"><strong>Priority:</strong> {{ $task->priority_label }}</div>
                    <div class="col-lg-6"><strong>Assigned Staff:</strong> {{ $task->assignedUser?->name ?? 'Not assigned' }}</div>
                    <div class="col-lg-6"><strong>Estimated Cost:</strong> {{ number_format((float) $task->total_cost, 2) }} AED</div>
                    <div class="col-lg-6"><strong>Due Date:</strong> {{ $task->due_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-lg-6"><strong>Expected Completion:</strong> {{ $task->expected_completion_date?->format('d M Y') ?? '-' }}</div>
                    <div class="col-lg-6"><strong>Created By:</strong> {{ $task->createdBy?->name ?? 'System' }}</div>
                    <div class="col-lg-6"><strong>Created Date:</strong> {{ $task->created_at?->format('d M Y H:i') ?? '-' }}</div>
                    <div class="col-lg-12"><strong>Description:</strong> {{ $task->description ?: '-' }}</div>
                    @if($task->completion_notes)
                        <div class="col-lg-12"><strong>Completion Notes:</strong> {{ $task->completion_notes }}</div>
                    @endif
                </div>
                <div class="mt-4">
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" style="width: {{ (int) $task->progress }}%"></div>
                    </div>
                    <p class="text-muted small mb-0 mt-1">Progress {{ (int) $task->progress }}%</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Timeline</h4></div>
            <div class="card-body">
                @foreach(['Created' => $task->created_at, 'Assigned' => $task->assigned_to ? $task->created_at : null, 'Accepted' => $task->accepted_at, 'Started' => $task->started_at, 'Completed' => $task->completed_at] as $label => $date)
                    <div class="border-start border-2 ps-3 mb-3">
                        <h6 class="mb-1">{{ $label }}</h6>
                        <p class="text-muted mb-0">{{ $date ? $date->format('d M Y H:i') : 'Pending' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        @if($task->inspection)
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Inspection Report</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.inspection.show', $task->inspection->id) }}" class="btn btn-sm btn-primary">View Report</a>
                        <a href="{{ route('admin.inspection.pdf', $task->inspection->id) }}" class="btn btn-sm btn-outline-light">PDF</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>No:</strong> {{ $task->inspection->inspection_number }}</div>
                        <div class="col-md-4"><strong>Type:</strong> {{ $task->inspection->type_label }}</div>
                        <div class="col-md-4"><strong>Status:</strong> {{ $task->inspection->status_label }}</div>
                        <div class="col-md-4"><strong>Total:</strong> {{ $task->inspection->total_items }}</div>
                        <div class="col-md-4"><strong>Good:</strong> <span class="text-success">{{ $task->inspection->good_items }}</span></div>
                        <div class="col-md-4"><strong>Issues:</strong> <span class="text-danger">{{ $task->inspection->issue_items }}</span></div>
                    </div>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Cost Items</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-centered mb-0">
                        <thead class="bg-light-subtle">
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Worker</th>
                                <th>Qty / Hours</th>
                                <th>Rate</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($task->costItems as $item)
                                <tr>
                                    <td>{{ ucfirst($item->type) }}</td>
                                    <td>{{ $item->label }}</td>
                                    <td>{{ $item->worker ?: '-' }}</td>
                                    <td>{{ $item->hours ?? $item->quantity ?? '-' }}</td>
                                    <td>{{ $item->rate ?? $item->unit_price ?? '-' }}</td>
                                    <td class="text-end">AED {{ number_format((float) $item->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No cost items yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Remarks</h4></div>
            <div class="card-body">
                @forelse($task->remarks as $remark)
                    <div class="border-bottom pb-3 mb-3">
                        <p class="mb-1">{{ $remark->remark }}</p>
                        <p class="text-muted small mb-2">{{ $remark->user?->name ?? 'System' }} | {{ $remark->created_at?->format('d M Y H:i') }} @if($remark->status_update) | {{ ucfirst(str_replace('_', ' ', $remark->status_update)) }} @endif</p>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach((array) $remark->pictures as $picture)
                                <a href="{{ \App\Support\MediaStorage::url($picture) }}" target="_blank" class="badge bg-light-subtle text-muted border">Attachment</a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No remarks yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Assign / Track</h4></div>
            <div class="card-body">
                <form action="{{ route('admin.task.update', $task->id) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')
                    <div class="col-lg-12">
                        <label for="assigned_to" class="form-label">Assigned Staff</label>
                        <select id="assigned_to" name="assigned_to" class="form-select">
                            <option value="">Not assigned</option>
                            @foreach($maintainers as $maintainer)
                                <option value="{{ $maintainer->id }}" @selected($task->assigned_to === $maintainer->id)>{{ $maintainer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label for="priority" class="form-label">Priority</label>
                        <select id="priority" name="priority" class="form-select" required>
                            @foreach(\App\Models\BookingTask::PRIORITIES as $key => $label)
                                <option value="{{ $key }}" @selected($task->priority === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" id="due_date" name="due_date" class="form-control" value="{{ $task->due_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-lg-12">
                        <label for="progress" class="form-label">Progress %</label>
                        <input type="number" min="0" max="100" id="progress" name="progress" class="form-control" value="{{ (int) $task->progress }}">
                    </div>
                    <div class="col-lg-12">
                        <button class="btn btn-primary w-100">Save Task</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Attachments</h4></div>
            <div class="card-body d-flex flex-wrap gap-2">
                @foreach((array) $task->pictures as $picture)
                    <a href="{{ \App\Support\MediaStorage::url($picture) }}" target="_blank" class="badge bg-light-subtle text-muted border">Task Image</a>
                @endforeach
                @foreach((array) $task->final_images as $picture)
                    <a href="{{ \App\Support\MediaStorage::url($picture) }}" target="_blank" class="badge bg-success-subtle text-success border">Final Image</a>
                @endforeach
                @if($task->invoice_attachment)<a href="{{ \App\Support\MediaStorage::url($task->invoice_attachment) }}" target="_blank" class="badge bg-primary-subtle text-primary border">Invoice</a>@endif
                @if($task->receipt_attachment)<a href="{{ \App\Support\MediaStorage::url($task->receipt_attachment) }}" target="_blank" class="badge bg-success-subtle text-success border">Receipt</a>@endif
                @if($task->warranty_attachment)<a href="{{ \App\Support\MediaStorage::url($task->warranty_attachment) }}" target="_blank" class="badge bg-warning-subtle text-warning border">Warranty</a>@endif
                @if(! $task->pictures && ! $task->final_images && ! $task->invoice_attachment && ! $task->receipt_attachment && ! $task->warranty_attachment)
                    <p class="text-muted mb-0">No attachments.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Cost Summary</h4></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><td>Labor Cost</td><td class="text-end">AED {{ number_format((float) $task->labor_cost, 2) }}</td></tr>
                        <tr><td>Materials Cost</td><td class="text-end">AED {{ number_format((float) $task->material_cost, 2) }}</td></tr>
                        <tr><td>Other Expenses</td><td class="text-end">AED {{ number_format((float) $task->other_expenses, 2) }}</td></tr>
                        <tr class="fw-semibold"><td>Total Cost</td><td class="text-end">AED {{ number_format((float) $task->total_cost, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Activity Log</h4></div>
            <div class="card-body">
                @forelse($task->activities as $activity)
                    <div class="border-start border-2 ps-3 mb-3">
                        <h6 class="mb-1">{{ $activity->action }}</h6>
                        <p class="mb-1">{{ $activity->comment ?: '-' }}</p>
                        <p class="text-muted small mb-0">
                            {{ $activity->user?->name ?? 'System' }} | {{ $activity->created_at?->format('d M Y H:i') }}
                            @if($activity->gps_latitude && $activity->gps_longitude)
                                | GPS: {{ $activity->gps_latitude }}, {{ $activity->gps_longitude }}
                            @endif
                        </p>
                    </div>
                @empty
                    <p class="text-muted mb-0">No activity yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
