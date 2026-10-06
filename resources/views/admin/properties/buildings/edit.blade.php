@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="mb-1">Edit Building</h4><p class="text-muted mb-0">Update building contacts, address and management email.</p></div>
    <a href="{{ route('admin.building.index') }}" class="btn btn-light">Back to Buildings</a>
</div>

<div class="card"><div class="card-body">
    <form action="{{ route('admin.building.update', $building) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6"><label for="building_name" class="form-label">Building Name *</label><input id="building_name" name="building_name" class="form-control @error('building_name') is-invalid @enderror" value="{{ old('building_name', $building->building_name) }}" required>@error('building_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label for="management_email" class="form-label">Management Email</label><input type="email" id="management_email" name="management_email" class="form-control @error('management_email') is-invalid @enderror" value="{{ old('management_email', $building->management_email) }}">@error('management_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label for="security_contact" class="form-label">Security Contact</label><input id="security_contact" name="security_contact" class="form-control" value="{{ old('security_contact', $building->security_contact) }}"></div>
            <div class="col-md-6"><label for="gas_provider" class="form-label">Gas Provider</label><input id="gas_provider" name="gas_provider" class="form-control" value="{{ old('gas_provider', $building->gas_provider) }}"></div>
            <div class="col-12"><label for="address" class="form-label">Address *</label><textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3" required>{{ old('address', $building->address) }}</textarea>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label for="city" class="form-label">City</label><input id="city" name="city" class="form-control" value="{{ old('city', $building->city) }}"></div>
            <div class="col-md-4"><label for="state" class="form-label">State</label><input id="state" name="state" class="form-control" value="{{ old('state', $building->state) }}"></div>
            <div class="col-md-4"><label for="country" class="form-label">Country</label><input id="country" name="country" class="form-control" value="{{ old('country', $building->country) }}"></div>
            <div class="col-md-8"><label for="google_map_link" class="form-label">Google Map Link</label><input type="url" id="google_map_link" name="google_map_link" class="form-control @error('google_map_link') is-invalid @enderror" value="{{ old('google_map_link', $building->google_map_link) }}">@error('google_map_link')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label for="year_built" class="form-label">Year Built</label><input type="number" id="year_built" name="year_built" class="form-control @error('year_built') is-invalid @enderror" min="1800" max="{{ now()->year }}" value="{{ old('year_built', $building->year_built) }}">@error('year_built')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('admin.building.index') }}" class="btn btn-light">Cancel</a><button class="btn btn-primary">Save Building</button></div>
    </form>
</div></div>
@endsection
