@extends('layouts.app')
@section('title','Financial Approvals')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h4 class="mb-1">Financial Approvals</h4><p class="text-muted mb-0">Independent Admin or Accounting review before payments, edits or deletions affect accounts.</p></div>
    <div class="btn-group">
        @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $key=>$label)<a class="btn {{ $status===$key?'btn-primary':'btn-outline-primary' }}" href="{{ route('admin.financial-approvals.index',['status'=>$key]) }}">{{ $label }}</a>@endforeach
    </div>
</div>
<div class="alert alert-info"><i class="ri-shield-check-line me-1"></i><strong>Maker–Checker control:</strong> Admin and Accounting reviewers can approve, but the person who submits a request cannot approve it. Pending requests do not change invoice status, bank balances, owner statements or send payment emails.</div>
<div class="card"><div class="card-body p-0"><div class="table-responsive"><table class="table align-middle mb-0">
<thead class="table-light"><tr><th>Requested</th><th>Action</th><th>Booking / Invoice</th><th>Payment details</th><th>Requested by</th><th>Status / Review</th><th class="text-end">Decision</th></tr></thead>
<tbody>@forelse($requests as $item)
@php($payload=$item->payload ?? [])
<tr>
<td>{{ $item->requested_at?->timezone('Asia/Dubai')->format('d M Y H:i') }}<small class="d-block text-muted">Dubai time</small></td>
<td><strong>{{ $item->type_label }}</strong><small class="d-block text-primary">{{ $item->approval_no }}</small><small class="d-block text-muted">{{ $item->request_reason ?: '—' }}</small></td>
<td>@if($item->booking)<a href="{{ route('admin.booking.show',$item->booking) }}">{{ $item->booking->booking_reference }}</a><small class="d-block">{{ $item->booking->guest_name }}</small>@endif<small class="d-block text-muted">{{ $item->invoice?->invoice_number }}</small></td>
<td>@if(isset($payload['amount']))<strong>AED {{ number_format((float)$payload['amount'],2) }}</strong>@endif<small class="d-block">{{ $payload['payment_date'] ?? '' }} · {{ $payload['payment_method'] ?? '' }}</small><small class="d-block text-muted">Ref: {{ $payload['reference'] ?? '—' }}</small>@if($item->proof_path)<a class="btn btn-sm btn-outline-secondary mt-1" target="_blank" href="{{ route('admin.financial-approvals.proof',$item) }}"><i class="ri-attachment-line"></i> View proof</a>@endif @if($item->before_snapshot)<details class="mt-1"><summary class="small text-primary">Before / requested change</summary><pre class="small bg-light p-2">{{ json_encode(['before'=>$item->before_snapshot,'requested'=>$payload], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details>@endif</td>
<td>{{ $item->requester?->name }}<small class="d-block text-muted">{{ $item->requester?->email }}</small></td>
<td><span class="badge {{ $item->status==='pending'?'bg-warning text-dark':($item->status==='approved'?'bg-success':'bg-danger') }}">{{ ucfirst($item->status) }}</span>@if($item->reviewer)<small class="d-block mt-1">{{ $item->reviewer->name }} · {{ $item->reviewed_at?->timezone('Asia/Dubai')->format('d M H:i') }}</small><small class="d-block text-muted">{{ $item->review_notes }}</small>@endif</td>
<td class="text-end">@if($item->status==='pending')
 @if((string)$item->requested_by===(string)auth()->id())<span class="small text-muted">Another Admin or Accounting user must review</span>@else
 <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approve-{{ $item->id }}">Approve</button>
 <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-{{ $item->id }}">Reject</button>
 @endif
@else—@endif</td></tr>
@if($item->status==='pending' && (string)$item->requested_by!==(string)auth()->id())
<div class="modal fade" id="approve-{{ $item->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.financial-approvals.approve',$item) }}">@csrf<div class="modal-header"><h5 class="modal-title">Approve {{ $item->type_label }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="alert alert-warning">Approval immediately posts the transaction and may update the invoice, bank balance and owner accounts.</div><label class="form-label">Reviewer notes (optional)</label><textarea class="form-control" name="review_notes" rows="3"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Approve &amp; Post</button></div></form></div></div></div>
<div class="modal fade" id="reject-{{ $item->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.financial-approvals.reject',$item) }}">@csrf<div class="modal-header"><h5 class="modal-title">Reject request</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Rejection reason</label><textarea class="form-control" name="review_notes" minlength="5" rows="3" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reject</button></div></form></div></div></div>
@endif
@empty<tr><td colspan="7" class="text-center text-muted py-5">No {{ $status }} financial approval requests.</td></tr>@endforelse</tbody>
</table></div></div>@if($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif</div>
@endsection
