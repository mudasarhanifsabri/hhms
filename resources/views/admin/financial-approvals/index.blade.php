@extends('layouts.app')
@section('title','Financial Approvals')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h4 class="mb-1">Financial Approvals</h4><p class="text-muted mb-0">Admin and Accounting users can view requests. Only a Manager can approve or reject them.</p></div>
    <div class="btn-group">
        @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $key=>$label)<a class="btn {{ $status===$key?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.financial-approvals.index',['status'=>$key]) }}">{{ $label }}</a>@endforeach
    </div>
</div>
<div class="alert alert-info"><i class="ri-shield-check-line me-1"></i><strong>Manager approval:</strong> Only a Manager can approve or reject. Once approved by a Manager, the transaction posts immediately without Accounting approval. Pending requests do not affect accounts.</div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table align-middle mb-0">
<thead class="table-light"><tr><th>Request</th><th>Booking / Unit</th><th>Period</th><th>Amount</th><th>Requested by</th><th>Status</th><th class="text-end">Action</th></tr></thead>
<tbody>@forelse($requests as $item)
@php
    $payload=$item->payload ?? [];
    $canManagerDecide=auth()->user()?->hasRole('Manager');
    $property=$item->booking?->property;
@endphp
<tr class="approval-row" role="button" data-bs-toggle="modal" data-bs-target="#details-{{ $item->id }}">
<td><strong>{{ $item->type_label }}</strong><small class="d-block text-primary">{{ $item->approval_no }}</small><small class="d-block text-muted">{{ $item->requested_at?->timezone('Asia/Dubai')->format('d M Y H:i') }}</small></td>
<td><strong>{{ $item->booking?->booking_reference ?? '—' }}</strong><small class="d-block">{{ $property?->name ?? '—' }} · {{ $property?->building?->building_name ?? 'No building' }}</small><small class="d-block text-muted">{{ $item->invoice?->invoice_number }}</small></td>
<td>@if($item->invoice){{ $item->invoice->period_from?->format('d M Y') ?? '—' }}<small class="d-block text-muted">to {{ $item->invoice->period_to?->format('d M Y') ?? '—' }}</small>@else—@endif</td>
<td><strong>{{ isset($payload['amount']) ? 'AED '.number_format((float)$payload['amount'],2) : '—' }}</strong><small class="d-block text-muted">Ref: {{ $payload['reference'] ?? '—' }}</small></td>
<td>{{ $item->requester?->name }}<small class="d-block text-muted">{{ $item->requester?->email }}</small></td>
<td><span class="badge {{ $item->status==='pending'?'bg-warning text-dark':($item->status==='approved'?'bg-success':'bg-danger') }}">{{ ucfirst($item->status) }}</span>@if($item->reviewer)<small class="d-block mt-1">{{ $item->reviewer->name }} · {{ $item->reviewed_at?->timezone('Asia/Dubai')->format('d M H:i') }}</small><small class="d-block text-muted">{{ $item->review_notes }}</small>@endif</td>
<td class="text-end" onclick="event.stopPropagation()">@if($item->status==='pending' && $canManagerDecide)<button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approve-{{ $item->id }}">Approve</button><button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-{{ $item->id }}">Reject</button>@elseif($item->status==='pending')<span class="small text-muted">Manager approval required</span>@else<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#details-{{ $item->id }}">View</button>@endif</td></tr>
@empty<tr><td colspan="7" class="text-center text-muted py-5">No {{ $status }} financial approval requests.</td></tr>@endforelse</tbody>
</table></div></div>@if($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif</div>

@foreach($requests as $item)
@php($payload=$item->payload ?? []) @php($property=$item->booking?->property) @php($canManagerDecide=auth()->user()?->hasRole('Manager'))
<div class="modal fade" id="details-{{ $item->id }}" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">{{ $item->type_label }}</h5><small class="text-primary">{{ $item->approval_no }}</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
<div class="row g-3 mb-3"><div class="col-md-4"><small class="text-muted">Booking</small><div class="fw-semibold">{{ $item->booking?->booking_reference ?? '—' }}</div><div>{{ $item->booking?->guest_name }}</div></div><div class="col-md-4"><small class="text-muted">Unit / Building</small><div class="fw-semibold">{{ $property?->name ?? '—' }}</div><div>{{ $property?->building?->building_name ?? 'No building' }}</div></div><div class="col-md-4"><small class="text-muted">Invoice period</small><div class="fw-semibold">{{ $item->invoice?->period_from?->format('d M Y') ?? '—' }}</div><div>to {{ $item->invoice?->period_to?->format('d M Y') ?? '—' }}</div></div></div>
<table class="table table-sm"><tbody><tr><th>Invoice</th><td>{{ $item->invoice?->invoice_number ?? '—' }}</td></tr><tr><th>Amount</th><td>{{ isset($payload['amount']) ? 'AED '.number_format((float)$payload['amount'],2) : '—' }}</td></tr><tr><th>Payment date</th><td>{{ $payload['payment_date'] ?? '—' }}</td></tr><tr><th>Method</th><td>{{ $payload['payment_method'] ?? '—' }}</td></tr><tr><th>Reference</th><td>{{ $payload['reference'] ?? '—' }}</td></tr><tr><th>Requested by</th><td>{{ $item->requester?->name }} · {{ $item->requester?->email }}</td></tr><tr><th>Reason / Notes</th><td>{{ $item->request_reason ?: '—' }}</td></tr></tbody></table>
@if($item->proof_path)<a class="btn btn-outline-secondary" target="_blank" href="{{ route('admin.financial-approvals.proof',$item) }}"><i class="ri-attachment-line"></i> View payment proof</a>@endif
@if($item->before_snapshot)<div class="alert alert-light border mt-3 mb-0"><strong>Existing record before requested change</strong><div class="small mt-2">@foreach($item->before_snapshot as $key=>$value)<div><span class="text-muted">{{ str($key)->replace('_',' ')->headline() }}:</span> {{ is_array($value) ? collect($value)->map(fn($v,$k)=>str($k)->replace('_',' ')->headline().': '.$v)->implode(' · ') : $value }}</div>@endforeach</div></div>@endif
</div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Close</button></div></div></div></div>
@if($item->status==='pending' && $canManagerDecide)
<div class="modal fade" id="approve-{{ $item->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.financial-approvals.approve',$item) }}">@csrf<div class="modal-header"><h5 class="modal-title">Approve {{ $item->type_label }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-warning">Manager approval posts this transaction immediately. No Accounting approval is required.</div><label class="form-label">Manager notes (optional)</label><textarea class="form-control" name="review_notes" rows="3"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Approve &amp; Post</button></div></form></div></div></div>
<div class="modal fade" id="reject-{{ $item->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.financial-approvals.reject',$item) }}">@csrf<div class="modal-header"><h5 class="modal-title">Reject request</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Rejection reason</label><textarea class="form-control" name="review_notes" minlength="5" rows="3" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reject</button></div></form></div></div></div>
@endif
@endforeach
<style>.approval-row{cursor:pointer}.approval-row:hover{background:#f8f7ff}.approval-row td{padding-top:.65rem;padding-bottom:.65rem}</style>
@endsection
