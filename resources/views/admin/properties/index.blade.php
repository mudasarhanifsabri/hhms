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
                        <h4 class="card-title mb-2">Vacant</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['vacant'] ?? 0 }} Unit</p>
                    </div>
                    <div class="avatar-md bg-success bg-opacity-10 rounded">
                        <iconify-icon icon="solar:key-minimalistic-square-broken" class="fs-32 text-success avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0 text-muted">No active guest stay</p>
                    <a href="{{ route('admin.property.index', ['status' => 'vacant']) }}" class="link-primary fw-medium">Filter</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Occupied</h4>
                        <p class="text-muted fw-medium fs-22 mb-0">{{ $unitStats['occupied'] ?? 0 }} Unit</p>
                    </div>
                    <div class="avatar-md bg-primary bg-opacity-10 rounded">
                        <iconify-icon icon="solar:calendar-mark-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <p class="mb-0 text-muted">Active stay not checked out</p>
                    <a href="{{ route('admin.property.index', ['status' => 'occupied']) }}" class="link-primary fw-medium">Filter</a>
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
                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#unitBalanceExportModal" data-export-format="excel"><i class="ri-file-excel-2-line me-1"></i>Export Excel</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#unitBalanceExportModal" data-export-format="pdf"><i class="ri-file-pdf-2-line me-1"></i>Export PDF</button>
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
                            <option value="vacant" @selected(in_array($status, ['vacant','available']))>Vacant</option>
                            <option value="occupied" @selected(in_array($status, ['occupied','booked']))>Occupied</option>
                            <option value="attention" @selected($status === 'attention')>Needs Attention</option>
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
                                    <span class="badge {{ $property->occupancy_class }} text-white">{{ $property->occupancy_label }}</span>
                                    @if($property->needs_attention)
                                        <span class="badge bg-warning-subtle text-warning d-block mt-1">{{ $property->status_label }}</span>
                                    @endif
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

<div class="modal fade" id="unitBalanceExportModal" tabindex="-1" aria-labelledby="unitBalanceExportLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" id="unitBalanceExportForm" action="{{ route('admin.property.export.pdf') }}">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="unitBalanceExportLabel">Export Monthly Unit Balances</h5>
                        <p class="text-muted mb-0 mt-1">Choose the statement month for the balance sheet.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="exportMonth" class="form-label">Statement month</label>
                    <input type="month" class="form-control form-control-lg" id="exportMonth" name="month" value="{{ now()->timezone('Asia/Dubai')->format('Y-m') }}" required>
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="alert alert-light border mt-3 mb-0">
                        The report includes opening balance, selected-month credits and debits, net movement, and closing balance for every matching unit.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="unitBalanceExportSubmit">Download PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('unitBalanceExportModal')?.addEventListener('show.bs.modal', function (event) {
    const format = event.relatedTarget?.getAttribute('data-export-format') || 'pdf';
    const form = document.getElementById('unitBalanceExportForm');
    const button = document.getElementById('unitBalanceExportSubmit');
    form.action = format === 'excel' ? @json(route('admin.property.export.excel')) : @json(route('admin.property.export.pdf'));
    button.textContent = format === 'excel' ? 'Download Excel' : 'Download PDF';
    button.className = format === 'excel' ? 'btn btn-success' : 'btn btn-danger';
});
</script>
@endsection
