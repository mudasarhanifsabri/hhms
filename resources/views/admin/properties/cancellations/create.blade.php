@extends('layouts.app')
@section('title','Create Unit Cancellation')
@section('content')
@include('admin.properties.partials.unit-tabs')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="mb-1">Management Agreement Cancellation</h4><p class="text-muted mb-0">{{ $property->building?->building_name }} — {{ $property->name }}</p></div><a href="{{ route('admin.property.show',$property) }}" class="btn btn-light">Back to Unit</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('admin.property.cancellations.store',$property) }}">@csrf<div class="row g-3"><div class="col-xl-8"><div class="card"><div class="card-header"><h4 class="card-title mb-0">Cancellation Details</h4></div><div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">Property Owner *</label><select name="owner_id" id="owner_id" class="form-select" required>@foreach($owners as $owner)<option value="{{ $owner->id }}" data-email="{{ $owner->email }}" @selected(old('owner_id',$property->landlord_id)===$owner->id)>{{ $owner->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Owner Email *</label><input type="email" name="owner_email" id="owner_email" class="form-control" value="{{ old('owner_email',$property->landlord?->email) }}" required></div>
<div class="col-md-6"><label class="form-label">Letter Date *</label><input type="date" name="letter_date" class="form-control" value="{{ old('letter_date',today()->toDateString()) }}" required></div>
<div class="col-md-6"><label class="form-label">Effective Cancellation Date *</label><input type="date" name="effective_date" class="form-control" value="{{ old('effective_date') }}" required></div>
<div class="col-12"><label class="form-label">Property Description *</label><textarea name="property_description" class="form-control" rows="3" required>{{ old('property_description','Unit '.$property->name.($property->room_no ? ' — Property No. '.$property->room_no : '').', '.collect([$property->building?->building_name,$property->building?->address,$property->community,$property->building?->city])->filter()->unique()->implode(', ')) }}</textarea></div>
</div></div></div><div class="col-xl-4"><div class="card"><div class="card-header"><h4 class="card-title mb-0">Company Signatory</h4></div><div class="card-body">
<label class="form-label">Authorized Signatory Name *</label><input name="company_signer_name" class="form-control mb-3" value="{{ old('company_signer_name',auth()->user()->name) }}" required><label class="form-label">Signatory Email *</label><input type="email" name="company_signer_email" class="form-control" value="{{ old('company_signer_email',auth()->user()->email) }}" required><div class="alert alert-info mt-3 mb-0 small">The owner signs first. The company link is sent automatically after the owner signature.</div>
</div></div><button class="btn btn-danger btn-lg w-100"><i class="ri-file-sign-line me-1"></i>Create &amp; Send to Owner</button></div></div></form>
@endsection
@push('scripts')<script>document.getElementById('owner_id')?.addEventListener('change',e=>{document.getElementById('owner_email').value=e.target.selectedOptions[0]?.dataset.email||''})</script>@endpush
