@extends('layouts.app')

@section('title', 'Unit Document Wallet')

@section('content')
@php
    $expiryBadge = fn ($status) => match ($status) {
        'expired' => 'bg-danger', 'expiring' => 'bg-warning text-dark',
        'valid' => 'bg-success', default => 'bg-secondary',
    };
@endphp
@include('admin.properties.partials.unit-tabs')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div><h4 class="mb-1">Unit Document Wallet</h4><p class="text-muted mb-0">{{ $property->building?->building_name }} — {{ $property->name }}</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.property.owner-documents.index', $property) }}" class="btn btn-outline-primary"><i class="ri-file-sign-line me-1"></i>Generate Agreements</a>
        <a href="{{ route('admin.property.show', $property) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Unit Details</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row">
    <div class="col-xl-4">
        <div class="card sticky-xl-top" style="top:90px">
            <div class="card-header"><h4 class="card-title mb-0">Add Unit Document</h4></div>
            <div class="card-body">
                <div class="alert alert-info py-2 small"><i class="ri-calendar-check-line me-1"></i>Management Contract dates automatically synchronize to the latest NOC and Management Letter for this unit.</div>
                <form method="POST" action="{{ route('admin.property.document-wallet.store', $property) }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12"><label class="form-label">Document Type</label><select name="type" class="form-select" required>@foreach($types as $value=>$label)<option value="{{ $value }}" @selected(old('type')===$value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Custom Document Name</label><input name="custom_title" class="form-control" value="{{ old('custom_title') }}" placeholder="Required when type is Custom"></div>
                    <div class="col-12"><label class="form-label">Related Owner</label><select name="owner_id" class="form-select"><option value="">All unit owners</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected(old('owner_id')===$owner->id)>{{ $owner->name }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Reference / Document No.</label><input name="reference_no" class="form-control" value="{{ old('reference_no') }}"></div>
                    <div class="col-6"><label class="form-label">Issue Date</label><input type="date" name="issue_date" class="form-control" value="{{ old('issue_date') }}" data-wallet-issue-date></div>
                    <div class="col-6"><label class="form-label">Expiry Date</label><input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}" data-wallet-expiry-date><small class="text-muted">Defaults to one year minus one day after issue.</small></div>
                    <div class="col-12"><label class="form-label">File</label><input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required><small class="text-muted">PDF or image, maximum 20 MB.</small></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea></div>
                    <div class="col-12"><button class="btn btn-primary w-100"><i class="ri-upload-cloud-line me-1"></i>Add to Wallet</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between"><h4 class="card-title mb-0">Uploaded Documents</h4><span class="badge bg-primary-subtle text-primary">{{ $documents->count() }}</span></div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="bg-light-subtle"><tr><th>Document</th><th>Owner</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            @forelse($documents as $document)
                <tr><td><strong>{{ $document->title }}</strong><div class="text-muted small">{{ $document->reference_no ?: 'No reference number' }}</div></td><td>{{ $document->owner?->name ?? 'All owners' }}</td><td>{{ $document->expires_at?->format('d M Y') ?? 'No expiry' }}</td><td><span class="badge {{ $expiryBadge($document->expiry_status) }}">{{ str($document->expiry_status)->headline() }}</span></td><td><div class="d-flex gap-1"><a href="{{ \App\Support\MediaStorage::url($document->file_path) }}" target="_blank" class="btn btn-sm btn-dark">Open</a><button class="btn btn-sm btn-soft-primary" data-bs-toggle="collapse" data-bs-target="#edit-{{ $document->id }}">Edit</button><form method="POST" action="{{ route('admin.property.document-wallet.destroy', [$property,$document]) }}" onsubmit="return confirm('Remove this document from the wallet?')">@csrf @method('DELETE')<button class="btn btn-sm btn-soft-danger">Delete</button></form></div></td></tr>
                <tr class="collapse" id="edit-{{ $document->id }}"><td colspan="5"><form method="POST" action="{{ route('admin.property.document-wallet.update', [$property,$document]) }}" enctype="multipart/form-data" class="row g-2 p-2">@csrf @method('PUT')<div class="col-md-3"><select name="type" class="form-select">@foreach($types as $value=>$label)<option value="{{ $value }}" @selected($document->type===$value)>{{ $label }}</option>@endforeach</select></div><div class="col-md-3"><input name="custom_title" class="form-control" value="{{ $document->custom_title }}" placeholder="Custom title"></div><div class="col-md-3"><select name="owner_id" class="form-select"><option value="">All owners</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected($document->owner_id===$owner->id)>{{ $owner->name }}</option>@endforeach</select></div><div class="col-md-3"><input name="reference_no" class="form-control" value="{{ $document->reference_no }}" placeholder="Reference no."></div><div class="col-md-3"><input type="date" name="issue_date" class="form-control" value="{{ $document->issue_date?->format('Y-m-d') }}" data-wallet-issue-date></div><div class="col-md-3"><input type="date" name="expires_at" class="form-control" value="{{ $document->expires_at?->format('Y-m-d') }}" data-wallet-expiry-date></div><div class="col-md-3"><input type="file" name="document" class="form-control"></div><div class="col-md-3"><button class="btn btn-primary w-100">Save Changes</button></div><div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notes">{{ $document->notes }}</textarea></div></form></td></tr>
            @empty<tr><td colspan="5" class="text-center text-muted py-5">No uploaded unit documents yet.</td></tr>@endforelse
            </tbody></table></div></div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Generated Agreements & Signing</h4></div>
            <div class="card-body"><div class="vstack gap-2">@forelse($signingDocuments as $document)<div class="border rounded p-3 d-flex align-items-center justify-content-between gap-2"><div><strong>{{ $document->title }}</strong><div class="text-muted small">{{ $document->reference_no }} · Expires {{ $document->expires_at?->format('d M Y') }}</div></div><div class="d-flex gap-2"><span class="badge {{ $document->status==='signed'?'bg-success':'bg-warning text-dark' }}">{{ $document->status_label }}</span><a class="btn btn-sm btn-outline-dark" target="_blank" href="{{ route('owner-documents.pdf',$document->signing_token) }}">Open</a></div></div>@empty<p class="text-muted mb-0">No generated agreements yet.</p>@endforelse</div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('change', function (event) {
    if (!event.target.matches('[data-wallet-issue-date]') || !event.target.value) return;
    const form = event.target.closest('form');
    const expiry = form?.querySelector('[data-wallet-expiry-date]');
    if (!expiry) return;
    const [year, month, day] = event.target.value.split('-').map(Number);
    const nextYear = new Date(Date.UTC(year + 1, month - 1, day));
    nextYear.setUTCDate(nextYear.getUTCDate() - 1);
    expiry.value = nextYear.toISOString().slice(0, 10);
});
</script>
@endpush
