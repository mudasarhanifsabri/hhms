@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="mb-1">{{ $building->building_name }}</h4><p class="text-muted mb-0">Building details and linked units</p></div>
    <div class="d-flex gap-2"><a href="{{ route('admin.building.index') }}" class="btn btn-light">Back</a><a href="{{ route('admin.building.edit', $building) }}" class="btn btn-primary"><i class="ri-edit-line me-1"></i>Edit Building</a></div>
</div>

<div class="row g-3">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body"><h5 class="mb-3">Building Information</h5><dl class="row mb-0">
        <dt class="col-sm-5">Management Email</dt><dd class="col-sm-7">{{ $building->management_email ?: '—' }}</dd>
        <dt class="col-sm-5">Security Contact</dt><dd class="col-sm-7">{{ $building->security_contact ?: '—' }}</dd>
        <dt class="col-sm-5">Gas Provider</dt><dd class="col-sm-7">{{ $building->gas_provider ?: '—' }}</dd>
        <dt class="col-sm-5">Year Built</dt><dd class="col-sm-7">{{ $building->year_built ?: '—' }}</dd>
        <dt class="col-sm-5">Address</dt><dd class="col-sm-7">{{ collect([$building->address, $building->city, $building->state, $building->country])->filter()->implode(', ') }}</dd>
        <dt class="col-sm-5">Map</dt><dd class="col-sm-7">@if($building->google_map_link)<a href="{{ $building->google_map_link }}" target="_blank" rel="noopener">Open Google Maps</a>@else—@endif</dd>
    </dl></div></div></div>
    <div class="col-lg-7"><div class="card h-100"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">Linked Units</h5><span class="badge bg-primary-subtle text-primary">{{ $building->properties_count }}</span></div><div class="list-group list-group-flush">
        @forelse($building->properties as $property)<a href="{{ route('admin.property.show', $property) }}" class="list-group-item list-group-item-action d-flex justify-content-between"><span>{{ $property->name }}</span><span class="badge bg-light text-dark">{{ $property->status_label }}</span></a>@empty<div class="p-4 text-center text-muted">No units are linked to this building.</div>@endforelse
    </div></div></div>
</div>
@endsection
