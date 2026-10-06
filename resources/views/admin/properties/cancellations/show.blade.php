@extends('layouts.app')
@section('title','Cancellation Tracking')
@section('content')
@include('admin.properties.partials.unit-tabs')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h4 class="mb-1">Cancellation Tracking</h4><p class="text-muted mb-0">{{ $cancellation->reference_no }} · {{ $property->building?->building_name }} — {{ $property->name }}</p></div><div class="d-flex gap-2">@if($cancellation->completed_at)<a target="_blank" class="btn btn-dark" href="{{ route('unit-cancellations.pdf',$cancellation->owner_token) }}">Final Signed PDF</a>@endif<a class="btn btn-light" href="{{ route('admin.property.show',$property) }}">Unit</a></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="card-header d-flex justify-content-between"><h4 class="card-title mb-0">Signing Progress</h4><span class="badge {{ $cancellation->completed_at?'bg-success':'bg-warning text-dark' }}">{{ $cancellation->status_label }}</span></div><div class="card-body"><div class="row g-3">
@foreach(['owner'=>['Owner',$cancellation->owner_name,$cancellation->owner_email,$cancellation->owner_sent_at,$cancellation->owner_viewed_at,$cancellation->owner_signed_at,$cancellation->owner_token], 'company'=>['Company',$cancellation->company_signer_name,$cancellation->company_signer_email,$cancellation->company_sent_at,$cancellation->company_viewed_at,$cancellation->company_signed_at,$cancellation->company_token]] as $party=>$info)
<div class="col-md-6"><div class="border rounded p-3 h-100"><h5>{{ $info[0] }}</h5><strong>{{ $info[1] }}</strong><div class="text-muted small mb-3">{{ $info[2] }}</div><div class="small">Sent: {{ $info[3]?->timezone('Asia/Dubai')->format('d M Y h:i A') ?? 'Not sent' }}<br>Viewed: {{ $info[4]?->timezone('Asia/Dubai')->format('d M Y h:i A') ?? 'Not viewed' }}<br>Signed: {{ $info[5]?->timezone('Asia/Dubai')->format('d M Y h:i A') ?? 'Not signed' }}</div><div class="d-flex flex-wrap gap-2 mt-3"><a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('unit-cancellations.show',$info[6]) }}">Open Link</a><button type="button" class="btn btn-sm btn-outline-secondary copy-signing-link" data-url="{{ route('unit-cancellations.show',$info[6]) }}">Copy Link</button>@if(!$info[5] && ($party==='owner'||$cancellation->owner_signed_at))<form method="POST" action="{{ route('admin.property.cancellations.resend',[$property,$cancellation,$party]) }}">@csrf<button class="btn btn-sm btn-light">Resend</button></form>@endif</div></div></div>
@endforeach
</div></div></div></div><div class="col-lg-4"><div class="card"><div class="card-header"><h4 class="card-title mb-0">Document</h4></div><div class="card-body"><dl><dt>Letter date</dt><dd>{{ $cancellation->letter_date->format('d M Y') }}</dd><dt>Effective cancellation</dt><dd>{{ $cancellation->effective_date->format('d M Y') }}</dd><dt>Expires</dt><dd>{{ $cancellation->expires_at->format('d M Y') }}</dd>@if($cancellation->document_hash)<dt>SHA-256</dt><dd class="text-break small">{{ $cancellation->document_hash }}</dd>@endif</dl></div></div></div></div>
<div class="card mt-3"><div class="card-header"><h4 class="card-title mb-0">Audit Trail</h4></div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Dubai Time</th><th>Party</th><th>Event</th><th>IP</th></tr></thead><tbody>@forelse($cancellation->events as $event)<tr><td>{{ $event->created_at->timezone('Asia/Dubai')->format('d M Y h:i:s A') }}</td><td>{{ str($event->actor_role)->headline() }}</td><td>{{ str($event->event)->replace('_',' ')->headline() }}</td><td>{{ $event->ip_address ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">No events yet.</td></tr>@endforelse</tbody></table></div></div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.copy-signing-link').forEach(button => button.addEventListener('click', async () => {
    await navigator.clipboard.writeText(button.dataset.url);
    const original = button.textContent;
    button.textContent = 'Copied';
    setTimeout(() => button.textContent = original, 1500);
}));
</script>
@endpush
