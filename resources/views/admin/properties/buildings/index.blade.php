@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div class="row justify-content-between">
                    <div class="col-lg-6">
                        <div class="row align-items-center">
                            <div class="col-lg-6">
                                <form class="app-search d-none d-md-block me-auto" method="GET">
                                    <div class="position-relative">
                                        <input type="search" name="search" class="form-control" placeholder="Search by name, email, or city" autocomplete="off" value="{{ request('search') }}">
                                        <iconify-icon icon="solar:magnifer-broken" class="search-widget-icon"></iconify-icon>
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-4">
                                <h5 class="text-dark fw-medium mb-0">Manage <span class="text-muted">Buildings</span></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="text-md-end mt-3 mt-md-0">

                            <a href="#" class="btn btn-success me-1" data-bs-toggle="modal" data-bs-target="#addBuildingModal">
                                <i class="ri-add-line"></i> New Building
                            </a>
                            <a href="#" class="btn btn-outline-secondary me-1">
                                <i class="ri-download-2-line"></i> Export CSV
                            </a>
                            @include('admin.properties.buildings.create')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                <h4 class="card-title mb-0">All Buildings List</h4>
                <div class="d-flex align-items-center gap-2">
                    <!-- Per Page Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded" data-bs-toggle="dropdown">
                            Show: <span id="selectedPerPage">{{ request('per_page', 10) }}</span> Rows
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach ([5, 10, 25, 50, 100] as $rows)
                                <a href="#" class="dropdown-item per-page-option" data-value="{{ $rows }}">{{ $rows }} Rows</a>
                            @endforeach
                        </div>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded" data-bs-toggle="dropdown">
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

            <div class="table-responsive">
                <table class="table align-middle text-nowrap table-hover table-centered mb-0">
                    <thead class="bg-light-subtle">
                        <tr>
                            <th style="width: 20px;"><input type="checkbox" class="form-check-input" id="customCheckAll"></th>
                            <th>Photo & Name</th>
                            <th>Management Email</th>
                            <th>Contacts</th>
                            <th>Units</th>
                            <th>Address</th>
                            <th>Map Link</th>
                            <th>
                                <span data-bs-toggle="tooltip" title="Year the building was constructed.">Year Built</span>
                            </th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($buildings as $building)
                            <tr>
                                <td><input type="checkbox" class="form-check-input" id="customCheck{{ $building->id }}"></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ asset('assets/images/properties/default-building.jpg') }}" alt="" class="avatar-md rounded border border-light border-3">
                                        <a href="#" class="text-dark fw-medium fs-15">{{ $building->building_name }}</a>
                                    </div>
                                </td>
                                <td>{{ $building->management_email ?? '—' }}</td>
                                <td>
                                    <div><strong>Security:</strong> {{ $building->security_contact ?? 'N/A' }}</div>
                                    <div><strong>Gas:</strong> {{ $building->gas_provider ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    @if ($building->unit_count)
                                        <span class="badge bg-info-subtle text-info py-1 px-2 fs-13">{{ $building->unit_count }} Units</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted py-1 px-2 fs-13">Not Set</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $building->address ?? '—' }},
                                    {{ $building->city ?? '' }},
                                    {{ $building->state ?? '' }},
                                    {{ $building->country ?? '' }}
                                </td>
                                <td>
                                    @if ($building->google_map_link)
                                        <a href="{{ $building->google_map_link }}" target="_blank" class="btn btn-sm btn-outline-secondary">View Map</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $building->year_built ?? '—' }}</td>
                                <td>{{ ucfirst($building->type ?? '—') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="#" class="btn btn-light btn-sm"><iconify-icon icon="solar:eye-broken" class="fs-18"></iconify-icon></a>
                                        <a href="#" class="btn btn-soft-primary btn-sm"><iconify-icon icon="solar:pen-2-broken" class="fs-18"></iconify-icon></a>
                                        <form action="#" method="POST" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-soft-danger btn-sm">
                                                <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="fs-18"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">No buildings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-end mb-0">
                        {{-- Previous --}}
                        @if ($buildings->onFirstPage())
                            <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $buildings->previousPageUrl() }}">Previous</a></li>
                        @endif

                        {{-- Pages --}}
                        @for ($page = 1; $page <= $buildings->lastPage(); $page++)
                            <li class="page-item {{ $page == $buildings->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $buildings->url($page) }}">{{ $page }}</a>
                            </li>
                        @endfor

                        {{-- Next --}}
                        @if ($buildings->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $buildings->nextPageUrl() }}">Next</a></li>
                        @else
                            <li class="page-item disabled"><a class="page-link" href="#">Next</a></li>
                        @endif
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Per Page -->
<form id="perPageForm" method="GET" style="display: none;">
    <input type="hidden" name="search" value="{{ request('search') }}">
    <input type="hidden" name="per_page" id="perPageInput" value="{{ request('per_page', 10) }}">
</form>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.per-page-option').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const perPage = this.getAttribute('data-value');
            document.getElementById('perPageInput').value = perPage;
            document.getElementById('perPageForm').submit();
        });
    });
</script>
@endpush
