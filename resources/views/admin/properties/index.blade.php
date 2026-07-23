@extends('layouts.app')

@section('content')

{{-- Top Stats Row --}}
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2 ">Total Incomes</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">$12,7812.09</p>
                    </div>
                    <div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded">
                            <iconify-icon icon="solar:wallet-money-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0">
                        <span class="text-success fw-medium mb-0">
                            <i class="ri-arrow-up-line"></i>34.4%
                        </span> vs last month
                    </p>
                    <div>
                        <a href="#!" class="link-primary fw-medium">
                            See Details <i class="ri-arrow-right-line align-middle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🔢 Total Properties (dynamic) --}}
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2 ">Total Properties</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">
                            {{ $totalProperties ?? 0 }} Unit
                        </p>
                    </div>
                    <div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded">
                            <iconify-icon icon="solar:home-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0">
                        <span class="text-danger fw-medium mb-0">
                            <i class="ri-arrow-down-line"></i>8.5%
                        </span> vs last month
                    </p>
                    <div>
                        <a href="#!" class="link-primary fw-medium">
                            See Details <i class="ri-arrow-right-line align-middle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- You can later make these 2 also dynamic --}}
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2 ">Unit Sold</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">893 Unit</p>
                    </div>
                    <div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded">
                            <iconify-icon icon="solar:dollar-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0">
                        <span class="text-success fw-medium mb-0">
                            <i class="ri-arrow-up-line"></i>17%
                        </span> vs last month
                    </p>
                    <div>
                        <a href="#!" class="link-primary fw-medium">
                            See Details <i class="ri-arrow-right-line align-middle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2 ">Unit Rent</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">459 Unit</p>
                    </div>
                    <div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded">
                            <iconify-icon icon="solar:key-minimalistic-square-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0">
                        <span class="text-danger fw-medium mb-0">
                            <i class="ri-arrow-down-line"></i>12%
                        </span> vs last month
                    </p>
                    <div>
                        <a href="#!" class="link-primary fw-medium">
                            See Details <i class="ri-arrow-right-line align-middle"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Properties Table --}}
<div class="row">
    <div class="col-xl-12">
        <div class="card">

            <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <h4 class="card-title mb-0">All Properties List</h4>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.property.create') }}" class="btn btn-sm btn-primary">
                        + Add New Property
                    </a>
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded" data-bs-toggle="dropdown" aria-expanded="false">
                            This Month
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="#!" class="dropdown-item">Download</a>
                            <a href="#!" class="dropdown-item">Export</a>
                            <a href="#!" class="dropdown-item">Import</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Success message --}}
            @if(session('success'))
                <div class="alert alert-success m-3">
                    {{ session('success') }}
                </div>
            @endif

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
                            <th>Properties Photo &amp; Name</th>
                            <th>Size</th>
                            <th>Property Type</th>
                            <th>Rent/Sale</th>
                            <th>Bedrooms</th>
                            <th>Location</th>
                            <th>Price</th>
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
                                $firstPhoto = !empty($photos) ? asset('storage/' . $photos[0]) : asset('assets/images/properties/p-1.jpg');
                            @endphp
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="property-{{ $property->id }}">
                                        <label class="form-check-label" for="property-{{ $property->id }}">&nbsp;</label>
                                    </div>
                                </td>

                                {{-- Photo + Name --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <img src="{{ $firstPhoto }}" alt="" class="avatar-md rounded border border-light border-3" />
                                        </div>
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('admin.property.show', $property->id) }}" class="text-dark fw-medium fs-15">
                                                {{ $property->name }}
                                            </a>
                                            <small class="text-muted">
                                                {{ optional($property->building)->building_name ?? 'No Building' }}
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                {{-- Size --}}
                                <td>
                                    {{ $property->square_foot ? $property->square_foot . ' ft' : '-' }}
                                </td>

                                {{-- Property Type --}}
                                <td>
                                    {{ $property->category ?? '—' }}
                                </td>

                                {{-- Rent/Sale badge – simple logic: if rent > 0 => Rent --}}
                                <td>
                                    @if($property->rent)
                                        <span class="badge bg-success-subtle text-success py-1 px-2 fs-13">Rent</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning py-1 px-2 fs-13">N/A</span>
                                    @endif
                                </td>

                                {{-- Bedrooms --}}
                                <td>
                                    <p class="mb-0">
                                        <iconify-icon icon="solar:bed-broken" class="align-middle fs-16"></iconify-icon>
                                        {{ $property->bedrooms ?? 0 }}
                                    </p>
                                </td>

                                {{-- Location --}}
                                <td>
                                    {{ optional($property->building)->location ?? optional($property->building)->name ?? '—' }}
                                </td>

                                {{-- Price / Rent --}}
                                <td>
                                    @if($property->rent)
                                        {{ number_format($property->rent, 2) }} AED
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    <span class="badge {{ $property->status_class }} text-white">{{ $property->status_label }}</span>
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div class="d-flex gap-2">
                                       <a href="{{ route('admin.property.show', ['property' => $property->id]) }}" class="btn btn-light btn-sm">
    <iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon>
</a>
                                        <a href="{{ route('admin.property.edit', $property->id) }}" class="btn btn-soft-primary btn-sm">
                                            <iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon>
                                        </a>
                                        <a href="{{ route('admin.property.owner-documents.index', $property->id) }}" class="btn btn-soft-success btn-sm" title="Owner Documents">
                                            <iconify-icon icon="solar:document-add-broken" class="align-middle fs-18"></iconify-icon>
                                        </a>
                                        <form action="{{ route('admin.property.destroy', $property->id) }}" method="POST" onsubmit="return confirm('Delete this property?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-soft-danger btn-sm">
                                                <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="align-middle fs-18"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <h5 class="text-muted mb-0">No properties found.</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Laravel Pagination --}}
            <div class="card-footer">
                <nav aria-label="Page navigation example">
                    {{ $properties->links('pagination::bootstrap-5') }}
                </nav>
            </div>
        </div>
    </div>
</div>

@endsection
