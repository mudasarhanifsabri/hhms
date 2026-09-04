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
                                       <form class="app-search me-auto" method="GET" action="{{ route('admin.landlord.index') }}">
                                            <div class="position-relative">
                                                 <input type="search" name="search" class="form-control" placeholder="Search name, email, phone or ID" autocomplete="off" value="{{ $search }}">
                                                 <input type="hidden" name="status" value="{{ $status }}">
                                                 <iconify-icon icon="solar:magnifer-broken" class="search-widget-icon"></iconify-icon>
                                            </div>
                                       </form>
                                  </div>
                                  <div class="col-lg-4">
                                       <h5 class="text-dark fw-medium mb-0">{{ $totalLandlords }} <span class="text-muted"> Owners</span></h5>
                                  </div>
                             </div>
                        </div>
                        <div class="col-lg-6">
                             <div class="text-md-end mt-3 mt-md-0">

                                  <form method="GET" action="{{ route('admin.landlord.index') }}" class="d-inline-flex gap-1 me-1">
                                    <input type="hidden" name="search" value="{{ $search }}">
                                    <select name="status" class="form-select" onchange="this.form.submit()">
                                        <option value="">All Status</option>
                                        <option value="active" @selected($status === 'active')>Active</option>
                                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                                    </select>
                                    @if($search !== '' || $status)
                                        <a href="{{ route('admin.landlord.index') }}" class="btn btn-outline-secondary">Reset</a>
                                    @endif
                                  </form>
                                  <a href="{{ route('admin.landlord.create') }}" class="btn btn-success me-1">
                                    <i class="ri-add-line"></i> New Owner </a>
                             </div>
                        </div><!-- end col-->
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
                        <h4 class="card-title">All Owners List</h4>
                   </div>

                   <div class="d-flex justify-content-start align-items-center mb-3">
                    <!-- Per Page Dropdown -->
                    <div class="dropdown me-3">
                        <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded" data-bs-toggle="dropdown" aria-expanded="false">
                            Show: <span id="selectedPerPage">{{ request('per_page', 10) }}</span> Rows
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="#" class="dropdown-item per-page-option" data-value="5">5 Rows</a>
                            <a href="#" class="dropdown-item per-page-option" data-value="10">10 Rows</a>
                            <a href="#" class="dropdown-item per-page-option" data-value="25">25 Rows</a>
                            <a href="#" class="dropdown-item per-page-option" data-value="50">50 Rows</a>
                            <a href="#" class="dropdown-item per-page-option" data-value="100">100 Rows</a>
                        </div>
                    </div>



                    <!-- This Month Dropdown -->
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle btn btn-sm btn-outline-light rounded" data-bs-toggle="dropdown" aria-expanded="false">
                            Export
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="{{ route('admin.landlord.pdf.list', request()->only('search', 'status')) }}" class="dropdown-item">PDF</a>
                            <a href="{{ route('admin.landlord.excel.list', request()->only('search', 'status')) }}" class="dropdown-item">Excel (CSV)</a>
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
                                       <th>Owner Photo &amp; Name</th>
                                       <th>Email</th>
                                       <th>Contact</th>
                                       <th>Units</th>
                                       <th>Account Balance</th>
                                       <th>Status</th>
                                       <th>Action</th>
                                  </tr>
                             </thead>
                             <tbody>
                                @foreach ($landlords as $landlord)
                                  <tr>
                                       <td>
                                            <div class="d-flex align-items-center gap-2">
                                                 <div>
                                                      @if($landlord->profile_photo)
                                                          <img src="{{ \App\Support\MediaStorage::url($landlord->profile_photo) }}" alt="{{ $landlord->name }}" class="avatar-sm rounded-circle">
                                                      @else
                                                          <span class="avatar-sm rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"><iconify-icon icon="solar:user-rounded-bold-duotone" class="fs-22"></iconify-icon></span>
                                                      @endif
                                                 </div>
                                                 <div>
                                                      <a href="{{ route('admin.landlord.show', $landlord->id) }}" class="text-dark fw-medium fs-15">{{ $landlord->name }}</a>
                                                 </div>
                                            </div>

                                       </td>
                                       <td>{{ $landlord->email ?? 'N/A' }}</td>
                                       <td>{{ $landlord->phone ?? 'N/A' }}</td>
                                       <td>
                                            <div class="fw-medium">{{ $landlord->owned_units_count ?? 0 }} Units</div>
                                            <small class="text-muted">
                                                {{ $landlord->available_units_count ?? 0 }} available / {{ $landlord->booked_units_count ?? 0 }} booked
                                            </small>
                                       </td>
                                       <td>
                                            <a href="{{ route('admin.landlord.account-statement', $landlord->id) }}" class="fw-semibold {{ ($landlord->account_balance ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                                AED {{ number_format((float) ($landlord->account_balance ?? 0), 2) }}
                                            </a>
                                       </td>
                                       <td><span class="badge {{ $landlord->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} py-1 px-2 fs-13">{{ $landlord->is_active ? 'Active' : 'Inactive' }}</span></td>
                                       <td>
                                            <div class="d-flex gap-2">
                                                 <a href="{{ route('admin.landlord.show', $landlord->id) }}" class="btn btn-light btn-sm"><iconify-icon icon="solar:eye-broken" class="align-middle fs-18"></iconify-icon></a>
                                                 <a href="{{ route('admin.landlord.edit', $landlord->id) }}" class="btn btn-soft-primary btn-sm"><iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon></a>
                                                 <!-- Delete Button -->
<!-- Trigger Button -->
<a href="#" class="btn btn-soft-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $landlord->id }}">
    <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="align-middle fs-18"></iconify-icon>
</a>

<!-- Modal -->
<div class="modal fade" id="deleteModal-{{ $landlord->id }}" tabindex="-1" aria-labelledby="deleteModalLabel-{{ $landlord->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header border-0 bg-light py-3">
                <div class="d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-3">
                        <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="fs-4"></iconify-icon>
                    </div>
                    <h5 class="modal-title text-danger m-0" id="deleteModalLabel-{{ $landlord->id }}">
                        Confirm Deletion
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center">
                <p class="mb-2">Are you sure you want to delete <strong>{{ $landlord->name }}</strong>?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>

            <div class="modal-footer border-0 d-flex justify-content-between px-4 pb-4">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.landlord.destroy', $landlord->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

                                                    <!-- End Delete Confirmation Modal -->



                                            </div>
                                       </td>
                                  </tr>

                                  @endforeach

                             </tbody>
                        </table>
                   </div>
                   <!-- end table-responsive -->
              </div>
              <div class="card-footer">
                <nav aria-label="Page navigation example">
                    <ul class="pagination justify-content-end mb-0">
                        @if ($landlords->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">Previous</span></li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $landlords->previousPageUrl() . '&per_page=' . request('per_page') }}">Previous</a>
                            </li>
                        @endif

                        @foreach ($landlords->getUrlRange(1, $landlords->lastPage()) as $page => $url)
                            <li class="page-item {{ $page == $landlords->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url . '&per_page=' . request('per_page') }}">{{ $page }}</a>
                            </li>
                        @endforeach

                        @if ($landlords->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $landlords->nextPageUrl() . '&per_page=' . request('per_page') }}">Next</a>
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



