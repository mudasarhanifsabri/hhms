@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="card-title mb-2">Total Units</h4>
                    <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['total'] ?? 0 }}</p>
                </div>
                <div class="avatar-md bg-primary bg-opacity-10 rounded">
                    <iconify-icon icon="solar:home-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="card-title mb-2">Available</h4>
                    <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['available'] ?? 0 }}</p>
                </div>
                <div class="avatar-md bg-success bg-opacity-10 rounded">
                    <iconify-icon icon="solar:key-minimalistic-square-broken" class="fs-32 text-success avatar-title"></iconify-icon>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="card-title mb-2">Booked</h4>
                    <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['booked'] ?? 0 }}</p>
                </div>
                <div class="avatar-md bg-primary bg-opacity-10 rounded">
                    <iconify-icon icon="solar:calendar-mark-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="card-title mb-2">Needs Attention</h4>
                    <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['attention'] ?? 0 }}</p>
                </div>
                <div class="avatar-md bg-warning bg-opacity-10 rounded">
                    <iconify-icon icon="solar:shield-warning-broken" class="fs-32 text-warning avatar-title"></iconify-icon>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div>
                <h4 class="card-title mb-1">Units Grid</h4>
                <p class="text-muted mb-0">Live unit records from the system.</p>
            </div>
            <a href="{{ route('admin.property.create') }}" class="btn btn-primary">
                <i class="ri-add-line me-1"></i>Add New Unit
            </a>
        </div>
        <form action="{{ route('admin.property.grid') }}" method="GET" class="row g-2 align-items-end mt-3">
            <div class="col-lg-5">
                <label for="q" class="form-label">Search Unit</label>
                <input type="text" id="q" name="q" class="form-control" value="{{ $search }}" placeholder="Unit, owner, building, community or type">
            </div>
            <div class="col-lg-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="available" @selected($status === 'available')>Available</option>
                    <option value="booked" @selected($status === 'booked')>Booked</option>
                    <option value="under_cleaning" @selected($status === 'under_cleaning')>Under Cleaning</option>
                    <option value="under_maintenance" @selected($status === 'under_maintenance')>Under Maintenance</option>
                </select>
            </div>
            <div class="col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="{{ route('admin.property.grid') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row">
    @forelse($properties as $property)
        @php
            $photos = is_array($property->photos)
                ? $property->photos
                : ($property->photos ? json_decode($property->photos, true) : []);
            $firstPhoto = !empty($photos) ? \App\Support\MediaStorage::url($photos[0]) : null;
            $location = $property->community ?: (optional($property->building)->building_name ?: optional($property->building)->address);
        @endphp
        <div class="col-xl-4 col-md-6">
            <div class="card overflow-hidden h-100">
                <div class="position-relative">
                    @if($firstPhoto)<img src="{{ $firstPhoto }}" alt="{{ $property->name }}" class="img-fluid w-100" style="height: 190px; object-fit: cover;">@else<div class="bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="height:190px"><iconify-icon icon="solar:home-bold-duotone" style="font-size:72px"></iconify-icon></div>@endif
                    <span class="position-absolute top-0 end-0 p-2">
                        <span class="badge {{ $property->status_class }} text-white fs-13">{{ $property->status_label }}</span>
                    </span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start gap-2">
                        <div class="avatar bg-light rounded flex-shrink-0">
                            <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('admin.property.show', $property->id) }}" class="text-dark fw-medium fs-16">{{ $property->name }}</a>
                            <p class="text-muted mb-0">{{ optional($property->building)->building_name ?? 'No Building' }}</p>
                        </div>
                    </div>
                    <div class="row mt-3 g-2">
                        <div class="col-6">
                            <span class="badge bg-light-subtle text-muted border fs-12">{{ $property->unit_type_label }}</span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-light-subtle text-muted border fs-12">{{ $property->square_foot ? $property->square_foot . ' ft' : 'Size N/A' }}</span>
                        </div>
                        <div class="col-12">
                            <span class="text-muted small">Owner</span>
                            <div class="fw-medium">{{ $property->landlord?->name ?? '-' }}</div>
                        </div>
                        <div class="col-12">
                            <span class="text-muted small">Location</span>
                            <div>{{ $location ?: '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                    <p class="fw-medium text-dark fs-16 mb-0">
                        {{ $property->rent ? number_format((float) $property->rent, 2) . ' AED' : 'Rent N/A' }}
                    </p>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.property.show', $property->id) }}" class="btn btn-light btn-sm" title="View Unit">
                            <iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon>
                        </a>
                        <a href="{{ route('admin.property.edit', $property->id) }}" class="btn btn-soft-primary btn-sm" title="Edit Unit">
                            <iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">No units found.</div>
            </div>
        </div>
    @endforelse
</div>

<div class="card mt-3">
    <div class="card-body">
        {{ $properties->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
