@extends('layouts.app')

@section('title', 'DTCM Permit List')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div><h4 class="mb-1">DTCM Permit List</h4><p class="text-muted mb-0">Compliance and expiry monitoring across all units.</p></div>
    <a href="{{ route('admin.property.index') }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Units</a>
</div>

<div class="row g-3 mb-3">
    @foreach([['Permits',$stats['total'],'primary','ri-file-shield-2-line'],['Urgent · 7 Days',$stats['urgent'],'warning','ri-alarm-warning-line'],['Expired',$stats['expired'],'danger','ri-close-circle-line'],['Missing',$stats['missing'],'secondary','ri-file-warning-line']] as [$label,$value,$color,$icon])
        <div class="col-md-3"><div class="card mb-0"><div class="card-body d-flex justify-content-between align-items-center"><div><p class="text-muted mb-1">{{ $label }}</p><h3 class="mb-0">{{ $value }}</h3></div><div class="avatar-md bg-{{ $color }}-subtle rounded d-flex align-items-center justify-content-center"><i class="{{ $icon }} fs-24 text-{{ $color }}"></i></div></div></div></div>
    @endforeach
</div>

<div class="card">
    <div class="card-body border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-lg-3"><label class="form-label">Search</label><input name="q" value="{{ $search }}" class="form-control" placeholder="Unit, building, permit number"></div>
            <div class="col-lg-2"><label class="form-label">Building</label><select name="building_id" class="form-select"><option value="">All buildings</option>@foreach($buildings as $building)<option value="{{ $building->id }}" @selected($buildingId==$building->id)>{{ $building->building_name }}</option>@endforeach</select></div>
            <div class="col-lg-2"><label class="form-label">Owner</label><select name="owner_id" class="form-select"><option value="">All owners</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected($ownerId==$owner->id)>{{ $owner->name }}</option>@endforeach</select></div>
            <div class="col-lg-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="">All statuses</option>@foreach(['valid'=>'Valid','expiring'=>'8–30 Days','urgent'=>'1–7 Days','expired'=>'Expired','missing'=>'Missing Permit'] as $value=>$label)<option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-lg-3 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="ri-filter-3-line me-1"></i>Filter</button><a href="{{ route('admin.property.dtcm-permits') }}" class="btn btn-light">Reset</a></div>
        </form>
    </div>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="bg-light-subtle"><tr><th>Unit / Building</th><th>Owner</th><th>Permit Number</th><th>Issue Date</th><th>Expiry Date</th><th>Days Remaining</th><th>Status</th><th>Reminder</th><th>Actions</th></tr></thead><tbody>
    @forelse($properties as $property)
        @php
            $permit=$property->dtcmPermit;
            $days=$permit?->expires_at ? today()->diffInDays($permit->expires_at,false) : null;
            $permitStatus=!$permit?'missing':(!$permit->expires_at?'no_expiry':($days<0?'expired':($days<=7?'urgent':($days<=30?'expiring':'valid'))));
            $badge=match($permitStatus){'valid'=>'bg-success','expiring'=>'bg-info','urgent'=>'bg-warning text-dark','expired'=>'bg-danger',default=>'bg-secondary'};
        @endphp
        <tr>
            <td><a class="fw-semibold" href="{{ route('admin.property.show',$property) }}">{{ $property->name }}</a><div class="text-muted small">{{ $property->building?->building_name ?? 'No building' }}</div></td>
            <td>{{ $property->landlord?->name ?? 'Not assigned' }}</td>
            <td>{{ $permit?->reference_no ?? '—' }}</td>
            <td>{{ $permit?->issue_date?->format('d M Y') ?? '—' }}</td>
            <td>{{ $permit?->expires_at?->format('d M Y') ?? '—' }}</td>
            <td class="fw-semibold {{ !is_null($days)&&$days<0?'text-danger':'' }}">{{ is_null($days)?'—':($days<0?abs((int)$days).' overdue':(int)$days.' days') }}</td>
            <td><span class="badge {{ $badge }}">{{ str($permitStatus)->replace('_',' ')->headline() }}</span></td>
            <td>{{ $permit?->expiry_reminder_sent_for?->equalTo($permit?->expires_at) ? 'Sent '.optional($permit->updated_at)->format('d M') : 'Not sent' }}</td>
            <td><div class="d-flex gap-1">@if($permit)<a href="{{ \App\Support\MediaStorage::url($permit->file_path) }}" target="_blank" class="btn btn-sm btn-dark">Open</a>@endif<a href="{{ route('admin.property.document-wallet.index',$property) }}" class="btn btn-sm btn-soft-primary">{{ $permit?'Renew / Edit':'Add Permit' }}</a><a href="{{ route('admin.property.show',$property) }}" class="btn btn-sm btn-light">Unit</a></div></td>
        </tr>
    @empty<tr><td colspan="9" class="text-center text-muted py-5">No units match these filters.</td></tr>@endforelse
    </tbody></table></div>
    @if($properties->hasPages())<div class="card-footer">{{ $properties->links() }}</div>@endif
</div>
@endsection
