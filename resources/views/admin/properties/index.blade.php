@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Total Units</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['total'] ?? 0 }} Unit</p>
                    </div>
                    <div class="avatar-md bg-primary bg-opacity-10 rounded">
                        <iconify-icon icon="solar:home-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0 text-muted">All unit records</p>
                    <a href="{{ route('admin.property.index') }}" class="link-primary fw-medium">View All</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Available</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['available'] ?? 0 }} Unit</p>
                    </div>
                    <div class="avatar-md bg-success bg-opacity-10 rounded">
                        <iconify-icon icon="solar:key-minimalistic-square-broken" class="fs-32 text-success avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0 text-muted">Ready for booking</p>
                    <a href="{{ route('admin.property.index', ['status' => 'available']) }}" class="link-primary fw-medium">Filter</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Booked</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['booked'] ?? 0 }} Unit</p>
                    </div>
                    <div class="avatar-md bg-primary bg-opacity-10 rounded">
                        <iconify-icon icon="solar:calendar-mark-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0 text-muted">Occupied / reserved</p>
                    <a href="{{ route('admin.property.index', ['status' => 'booked']) }}" class="link-primary fw-medium">Filter</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Needs Attention</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['attention'] ?? 0 }} Unit</p>
                    </div>
                    <div class="avatar-md bg-warning bg-opacity-10 rounded">
                        <iconify-icon icon="solar:shield-warning-broken" class="fs-32 text-warning avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0 text-muted">Cleaning / maintenance</p>
                    <a href="{{ route('admin.property.index', ['status' => 'under_cleaning']) }}" class="link-primary fw-medium">Filter</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h4 class="card-title mb-0">All Units List</h4>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.property.create') }}" class="btn btn-sm btn-primary">+ Add New Unit</a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif

            <div class="card-body border-bottom">
                <form action="{{ route('admin.property.index') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-lg-5">
                        <label for="q" class="form-label">Search Unit</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ $search }}" placeholder="Unit, building, community or type">
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
                        <a href="{{ route('admin.property.index') }}" class="btn btn-light">Reset</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle text-nowrap table-hover table-centered mb-0">
                    <thead class="bg-light-subtle">
                        <tr>
                            <th style="width: 20px;">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="checkAll">
                                    <label class="form-check-label" for="checkAll"></label>
                                </div>
                            </th>
                            <th>Unit Photo &amp; Name</th>
                            <th>Size</th>
                            <th>Unit Type</th>
                            <th>Owner</th>
                            <th>Listing</th>
                            <th>Location</th>
                            <th>Rent</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($properties as $property)
                            @php
                                $photos = is_array($property->photos)
                                    ? $property->photos
                                    : ($property->photos ? json_decode($property->photos, true) : []);
                                $firstPhoto = !empty($photos) ? \App\Support\MediaStorage::url($photos[0]) : null;
                                $location = $property->community
                                    ?: (optional($property->building)->address ?: optional($property->building)->building_name);
                            @endphp
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="property-{{ $property->id }}">
                                        <label class="form-check-label" for="property-{{ $property->id }}">&nbsp;</label>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($firstPhoto)<img src="{{ $firstPhoto }}" alt="{{ $property->name }}" class="avatar-md rounded border border-light border-3" />@else<span class="avatar-md rounded border border-light border-3 bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"><iconify-icon icon="solar:home-bold-duotone" class="fs-28"></iconify-icon></span>@endif
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('admin.property.show', $property->id) }}" class="text-dark fw-medium fs-15">{{ $property->name }}</a>
                                            <small class="text-muted">{{ optional($property->building)->building_name ?? 'No Building' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $property->square_foot ? $property->square_foot . ' ft' : '-' }}</td>
                                <td>{{ $property->unit_type_label }}</td>
                                <td>
                                    <div class="fw-medium">{{ $property->landlord?->name ?? '-' }}</div>
                                    @if($property->ownerShares->count() > 1)
                                        <small class="text-muted">{{ $property->ownerShares->count() }} shared owners</small>
                                    @else
                                        <small class="text-muted">{{ $property->landlord?->phone ?: 'No phone' }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($property->rent)
                                        <span class="badge bg-success-subtle text-success py-1 px-2 fs-13">Rent</span>
                                    @else
                                        <span class="badge bg-light text-muted py-1 px-2 fs-13">Not Priced</span>
                                    @endif
                                </td>
                                <td>{{ $location ?: '-' }}</td>
                                <td>
                                    @if($property->rent)
                                        {{ number_format((float) $property->rent, 2) }} AED
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $property->status_class }} text-white">{{ $property->status_label }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.property.show', ['property' => $property->id]) }}" class="btn btn-light btn-sm" title="View Unit">
                                            <iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon>
                                        </a>
                                        <a href="{{ route('admin.property.edit', $property->id) }}" class="btn btn-soft-primary btn-sm" title="Edit Unit">
                                            <iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon>
                                        </a>
                                        <a href="{{ route('admin.property.owner-documents.index', $property->id) }}" class="btn btn-soft-success btn-sm" title="Owner Documents">
                                            <iconify-icon icon="solar:document-add-broken" class="align-middle fs-18"></iconify-icon>
                                        </a>
                                        <form action="{{ route('admin.property.destroy', $property->id) }}" method="POST" onsubmit="return confirm('Delete this unit?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-soft-danger btn-sm" title="Delete Unit">
                                                <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="align-middle fs-18"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <h5 class="text-muted mb-0">No units found.</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                {{ $properties->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
