@extends('layouts.app')

@section('content')
@if(count($inventoryRows))
<div class="card"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">Inventory review</h5><span class="badge bg-primary">{{ ucfirst($inventoryReview->status) }}</span></div>
<div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Item</th><th>Required</th><th>Found</th><th>Damaged</th><th>Missing</th><th>New loss / damage</th><th>Estimate AED</th><th>Notes</th></tr></thead><tbody>
@foreach($inventoryRows as $row)<tr><td>{{ $row['room'] }} — {{ $row['name'] }}</td><td>{{ $row['required'] }}</td><td>{{ $row['found'] ?? 'Pending' }}</td><td>{{ $row['damaged'] ?? 'Pending' }}</td><td>{{ $row['missing'] ?? '—' }}</td><td>{{ $row['new_missing'] ?? '—' }} / {{ $row['new_damaged'] ?? '—' }}</td><td>{{ isset($row['estimate']) ? number_format($row['estimate'],2) : 'No baseline' }}</td><td>{{ $row['notes'] ?? '' }}</td></tr>@endforeach
</tbody></table></div><div class="card-footer"><p class="small text-muted">New damage/loss estimates require an approved check-in inventory for this booking. Estimates require evidence review and are not automatic guest deductions.</p>
@if($inventoryReview->status === 'submitted')<form method="POST" action="{{ route('admin.inventory.approve',$inspection) }}" class="d-flex flex-wrap gap-2">@csrf<label class="flex-grow-1">Review notes<input name="notes" minlength="5" maxlength="2000" class="form-control" required placeholder="Review quantities and photos before approving"></label><label class="align-self-end"><input type="checkbox" name="create_task" value="1"> Create repair / replacement task</label><button class="btn btn-primary align-self-end">Approve stock counts</button></form>@elseif($inventoryReview->status === 'approved')<small>Approved {{ $inventoryReview->reviewed_at }} · {{ $inventoryReview->notes }}</small>@endif
</div></div>
@endif
<div class="row">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div><h4 class="card-title mb-1">{{ $inspection->inspection_number }}</h4><p class="text-muted mb-0">{{ $inspection->booking?->booking_reference ?? $inspection->task?->task_display_number ?? 'Unit inspection' }} - {{ $inspection->booking?->guest_name ?? $inspection->submittedBy?->name ?? 'Maintainer' }}</p></div>
                <a href="{{ route('admin.inspection.pdf', $inspection->id) }}" class="btn btn-primary btn-sm">PDF Report</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6"><strong>Type:</strong> {{ $inspection->type_label }}</div>
                    <div class="col-lg-6"><strong>Status:</strong> {{ $inspection->status_label }}</div>
                    <div class="col-lg-6"><strong>Property:</strong> {{ $inspection->booking?->property?->building?->name ?? $inspection->property?->building?->name ?? '-' }}</div>
                    <div class="col-lg-6"><strong>Unit:</strong> {{ $inspection->booking?->property?->name ?? $inspection->property?->name ?? '-' }}</div>
                    <div class="col-lg-6"><strong>Submitted By:</strong> {{ $inspection->submittedBy?->name ?? '-' }}</div>
                    <div class="col-lg-6"><strong>Submitted:</strong> {{ $inspection->submitted_at?->format('d M Y H:i') ?? '-' }}</div>
                    <div class="col-lg-12"><strong>Notes:</strong> {{ $inspection->notes ?: '-' }}</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Inspection Items</h4></div>
            <div class="card-body">
                @foreach($inspection->items->groupBy('area') as $area => $items)
                    <h5 class="mt-2">{{ $area }}</h5>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-centered">
                            <thead><tr><th>Item</th><th>Condition</th><th>Comment</th><th>Photos</th></tr></thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $item->item }}</td>
                                        <td><span class="badge {{ $item->condition === 'issue' ? 'bg-danger' : ($item->condition === 'good' ? 'bg-success' : 'bg-secondary') }}">{{ strtoupper($item->condition) }}</span></td>
                                        <td>{{ $item->comment ?: '-' }}</td>
                                        <td>
                                            @foreach((array) $item->pictures as $picture)
                                                <a href="{{ asset($picture) }}" target="_blank" class="badge bg-light-subtle text-muted border">Photo</a>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Summary</h4></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Total</td><td class="text-end">{{ $inspection->total_items }}</td></tr>
                    <tr><td>Good</td><td class="text-end text-success">{{ $inspection->good_items }}</td></tr>
                    <tr><td>Issues</td><td class="text-end text-danger">{{ $inspection->issue_items }}</td></tr>
                    <tr><td>N/A</td><td class="text-end">{{ $inspection->na_items }}</td></tr>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Deposit Decision Support</h4></div>
            <div class="card-body">
                @if($comparison['other'])
                    <p class="text-muted">Compared with {{ $comparison['other']->type_label }} inspection.</p>
                    @forelse($comparison['changed'] as $change)
                        <div class="border rounded p-2 mb-2">
                            <strong>{{ $change['area'] }} - {{ $change['item'] }}</strong>
                            <p class="mb-0 small">{{ strtoupper($change['check_in']) }} to {{ strtoupper($change['check_out']) }}</p>
                            <p class="mb-0 text-muted small">{{ $change['comment'] ?: '-' }}</p>
                        </div>
                    @empty
                        <p class="text-success mb-0">No condition changes found.</p>
                    @endforelse
                @else
                    <p class="text-muted mb-0">Submit both check-in and check-out inspections to compare for security deposit refund.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
