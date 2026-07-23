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
                                       <form class="app-search d-none d-md-block me-auto">
                                            <div class="position-relative">
                                                 <input type="search" class="form-control" placeholder="Search Maintainer" autocomplete="off" value="">
                                                 <iconify-icon icon="solar:magnifer-broken" class="search-widget-icon"></iconify-icon>
                                            </div>
                                       </form>
                                  </div>
                                  <div class="col-lg-4">
                                       <h5 class="text-dark fw-medium mb-0">{{ $totalMaintainers }} <span class="text-muted"> Maintainers</span></h5>
                                  </div>
                             </div>
                        </div>
                        <div class="col-lg-6">
                             <div class="text-md-end mt-3 mt-md-0">
                                  <button type="button" class="btn btn-outline-primary me-1"><i class="ri-filter-line me-1"></i> Filters</button>
                                  <a href="{{ route('admin.maintainer.create') }}" class="btn btn-success me-1">
                                    <i class="ri-add-line"></i> New Maintainer </a>
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
                   <div>
                        <h4 class="card-title">All Maintainers List</h4>
                   </div>

                   <div class="d-flex justify-content-start align-items-center mb-3">
                    <div class="dropdown me-3">
                        <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded" data-bs-toggle="dropdown" aria-expanded="false">
                            Show: <span id="selectedPerPage">{{ request('per_page', 10) }}</span> Rows
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach([5,10,25,50,100] as $perPage)
                                <a href="#" class="dropdown-item per-page-option" data-value="{{ $perPage }}">{{ $perPage }} Rows</a>
                            @endforeach
                        </div>
                    </div>

                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded" data-bs-toggle="dropdown" aria-expanded="false">
                            Export
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="{{ route('admin.maintainer.pdf.list') }}" class="dropdown-item">Pdf</a>
                        </div>
                    </div>
                </div>

                <script>
                    document.querySelectorAll('.per-page-option').forEach(item => {
                        item.addEventListener('click', function (e) {
                            e.preventDefault();
                            let perPage = this.getAttribute('data-value');
                            let url = new URL(window.location.href);
                            url.searchParams.set('per_page', perPage);
                            window.location.href = url.toString();
                        });
                    });
                </script>
              </div>
              <div class="card-body p-0">
                   <div class="table-responsive">
                        <table class="table align-middle text-nowrap table-hover table-centered mb-0">
                             <thead class="bg-light-subtle">
                                  <tr>
                                       <th>Maintainer Photo & Name</th>
                                       <th>Address</th>
                                       <th>Email</th>
                                       <th>Contact</th>
                                       <th>Assigned Units</th>
                                       <th>DOB</th>
                                       <th>Status</th>
                                       <th>Action</th>
                                  </tr>
                             </thead>
                             <tbody>
                                @foreach ($maintainers as $maintainer)
                                  <tr>
                                       <td>
                                            <div class="d-flex align-items-center gap-2">
                                                 <div>
                                                      <img src="{{ asset('/' . $maintainer->profile_photo) }}" alt="" class="avatar-sm rounded-circle">
                                                 </div>
                                                 <div>
                                                      <a href="{{ route('admin.maintainer.show', $maintainer->id) }}" class="text-dark fw-medium fs-15">{{ $maintainer->name }}</a>
                                                 </div>
                                            </div>
                                       </td>
                                       <td>{{ $maintainer->address ?? 'N/A' }}</td>
                                       <td>{{ $maintainer->email ?? 'N/A' }}</td>
                                       <td>{{ $maintainer->phone ?? 'N/A' }}</td>
                                       <td>3 Units</td>
                                       <td>{{ $maintainer->dob ? \Carbon\Carbon::parse($maintainer->dob)->format('d M Y') : 'N/A' }}</td>
                                       <td><span class="badge bg-success-subtle text-success py-1 px-2 fs-13">Active</span></td>
                                       <td>
                                            <div class="d-flex gap-2">
                                                 <a href="{{ route('admin.maintainer.show', $maintainer->id) }}" class="btn btn-light btn-sm"><iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon></a>
                                                 <a href="{{ route('admin.maintainer.edit', $maintainer->id) }}" class="btn btn-soft-primary btn-sm"><iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon></a>

                                                 <a href="#" class="btn btn-soft-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $maintainer->id }}">
                                                     <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="align-middle fs-18"></iconify-icon>
                                                 </a>

                                                 <div class="modal fade" id="deleteModal-{{ $maintainer->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $maintainer->id }}" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content shadow-lg border-0 rounded-3">
                                                            <div class="modal-header border-0 bg-light py-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-3">
                                                                        <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="fs-4"></iconify-icon>
                                                                    </div>
                                                                    <h5 class="modal-title text-danger m-0">Confirm Deletion</h5>
                                                                </div>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>

                                                            <div class="modal-body text-center">
                                                                <p class="mb-2">Are you sure you want to delete <strong>{{ $maintainer->name }}</strong>?</p>
                                                                <p class="text-muted small">This action cannot be undone.</p>
                                                            </div>

                                                            <div class="modal-footer border-0 d-flex justify-content-between px-4 pb-4">
                                                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                                <form action="{{ route('admin.maintainer.destroy', $maintainer->id) }}" method="POST">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger btn-sm">Yes, Delete</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                       </td>
                                  </tr>
                                @endforeach
                             </tbody>
                        </table>
                   </div>
              </div>
              <div class="card-footer">
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-end mb-0">
                        @if ($maintainers->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">Previous</span></li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $maintainers->previousPageUrl() . '&per_page=' . request('per_page') }}">Previous</a>
                            </li>
                        @endif

                        @foreach ($maintainers->getUrlRange(1, $maintainers->lastPage()) as $page => $url)
                            <li class="page-item {{ $page == $maintainers->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url . '&per_page=' . request('per_page') }}">{{ $page }}</a>
                            </li>
                        @endforeach

                        @if ($maintainers->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $maintainers->nextPageUrl() . '&per_page=' . request('per_page') }}">Next</a>
                            </li>
                        @else
                            <li class="page-item disabled"><span class="page-link">Next</span></li>
                        @endif
                    </ul>
                </nav>
              </div>
         </div>
    </div>
</div>
@endsection
