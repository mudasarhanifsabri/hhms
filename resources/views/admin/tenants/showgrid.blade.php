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
                                                 <input type="search" class="form-control" placeholder="Search Tenant" autocomplete="off" value="">
                                                 <iconify-icon icon="solar:magnifer-broken" class="search-widget-icon"></iconify-icon>
                                            </div>
                                       </form>
                                  </div>

                                  <div class="col-lg-4">
                                       <h5 class="text-dark fw-medium mb-0">{{ $totalTenants }} <span class="text-muted"> Tenants</span></h5>
                                  </div>
                             </div>
                        </div>
                        <div class="col-lg-6">
                             <div class="text-md-end mt-3 mt-md-0">
                                  <button type="button" class="btn btn-outline-primary me-1"><i class="ri-filter-line me-1"></i> Filters</button>
                                  <a href="{{ route('admin.tenant.create') }}" class="btn btn-success me-1">
                                    <i class="ri-add-line"></i> New Tenant
                                </a>
                             </div>
                        </div><!-- end col-->
                   </div>
              </div>
         </div>
    </div>
</div>

<div class="row">
    @foreach ($tenants as $tenant)
    <div class="col-xl-4 col-lg-6">
         <div class="card">
              <div class="card-body">
                   <div class="d-flex flex-wrap align-items-center gap-2 border-bottom pb-3">
                        @if($tenant->profile_photo)<img src="{{ \App\Support\MediaStorage::url($tenant->profile_photo) }}" alt="{{ $tenant->name }}" class="avatar-lg rounded-3 border border-light border-3">@else<span class="avatar-lg rounded-3 border border-light border-3 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center"><iconify-icon icon="solar:user-rounded-bold-duotone" class="fs-32"></iconify-icon></span>@endif
                        <div class="d-block">
                             <a href="{{ route('admin.tenant.show', $tenant->id) }}" class="text-dark fw-medium fs-16">{{ $tenant->name }}</a>
                             <p class="mb-0">{{ $tenant->email }}</p>
                             @if($tenant->tenant_profile_required)<span class="badge bg-warning text-dark">Profile pending</span>@endif
                             <p class="mb-0 text-primary">{{ $loop->iteration }}</p>
                        </div>
                        <div class="ms-auto">
                           <a href="{{ route('admin.tenant.edit', $tenant->id) }}" title="Edit Tenant">
                                <button type="button" class="btn btn-dark avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-light">
                                    <iconify-icon icon="solar:pen-new-square-broken"></iconify-icon>
                                </button>
                           </a>
                        </div>
                   </div>
                   <p class="mt-3 d-flex align-items-center gap-2 mb-2"><iconify-icon icon="solar:home-bold-duotone" class="fs-18 text-primary"></iconify-icon>Assigned Properties</p>
                   <p class="d-flex align-items-center gap-2 mt-2"><iconify-icon icon="solar:map-point-wave-bold-duotone" class="fs-18 text-primary"></iconify-icon>{{ $tenant->address ?? 'N/A' }}</p>
              </div>
              <div class="card-footer border-top">
                   <div class="row g-2">
                        <div class="col-lg-6">
                             <a href="tel:{{ $tenant->phone }}" class="btn btn-primary w-100">
                                  <iconify-icon icon="solar:outgoing-call-rounded-broken" class="align-middle fs-18"></iconify-icon> Call Us
                             </a>
                        </div>
                   </div>
              </div>
         </div>
    </div>
    @endforeach
</div>

<div class="p-3 border-top">
    <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-end mb-0">
            @if ($tenants->onFirstPage())
                <li class="page-item disabled"><span class="page-link">Previous</span></li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $tenants->previousPageUrl() . '&per_page=' . request('per_page') }}">Previous</a>
                </li>
            @endif

            @foreach ($tenants->getUrlRange(1, $tenants->lastPage()) as $page => $url)
                <li class="page-item {{ $page == $tenants->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url . '&per_page=' . request('per_page') }}">{{ $page }}</a>
                </li>
            @endforeach

            @if ($tenants->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $tenants->nextPageUrl() . '&per_page=' . request('per_page') }}">Next</a>
                </li>
            @else
                <li class="page-item disabled"><span class="page-link">Next</span></li>
            @endif
        </ul>
    </nav>
</div>

@endsection
