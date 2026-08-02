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
                                       <form class="app-search d-none d-md-block me-auto" method="GET" action="{{ route('admin.maintainer.index') }}">
                                            <div class="position-relative">
                                                 <input type="search" name="search" class="form-control" placeholder="Search Maintainer" autocomplete="off" value="{{ request('search') }}">
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
                                    <i class="ri-add-line"></i> New Maintainer
                                </a>
                             </div>
                        </div><!-- end col-->
                   </div>
              </div>
         </div>
    </div>
</div>

<div class="row">
    @foreach ($maintainers as $maintainer)
    <div class="col-xl-4 col-lg-6">
         <div class="card">
              <div class="card-body">
                   <div class="d-flex flex-wrap align-items-center gap-2 border-bottom pb-3">
                        <img src="{{ \App\Support\MediaStorage::url($maintainer->profile_photo) }}" alt="" class="avatar-lg rounded-3 border border-light border-3">
                        <div class="d-block">
                             <a href="{{ route('admin.maintainer.show', $maintainer->id) }}" class="text-dark fw-medium fs-16">{{ $maintainer->name }}</a>
                             <p class="mb-0">{{ $maintainer->email }}</p>
                             <p class="mb-0 text-primary">{{ $loop->iteration }}</p>
                        </div>
                        <div class="ms-auto">
                           <a href="{{ route('admin.maintainer.edit', $maintainer->id) }}" title="Edit Maintainer">
                              <button type="button" class="btn btn-dark avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-light">
                                <iconify-icon icon="solar:pen-new-square-broken"></iconify-icon>
                              </button>
                           </a>
                        </div>
                   </div>
                   <p class="mt-3 d-flex align-items-center gap-2 mb-2">
                      <iconify-icon icon="solar:tools-bold-duotone" class="fs-18 text-primary"></iconify-icon> 243 Tasks
                   </p>
                   <p class="d-flex align-items-center gap-2 mt-2">
                      <iconify-icon icon="solar:map-point-wave-bold-duotone" class="fs-18 text-primary"></iconify-icon>{{ $maintainer->location ?? 'N/A' }}
                   </p>
              </div>
              <div class="card-footer border-top">
                   <div class="row g-2">
                        <div class="col-lg-6">
                             <a href="tel:{{ $maintainer->phone }}" class="btn btn-primary w-100"><iconify-icon icon="solar:outgoing-call-rounded-broken" class="align-middle fs-18"></iconify-icon> Call Us</a>
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

@endsection
