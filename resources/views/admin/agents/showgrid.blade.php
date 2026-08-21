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
                                                 <input type="search" class="form-control" placeholder="Search Agent" autocomplete="off" value="">
                                                 <iconify-icon icon="solar:magnifer-broken" class="search-widget-icon"></iconify-icon>
                                            </div>
                                       </form>
                                  </div>

                                  <div class="col-lg-4">
                                       <h5 class="text-dark fw-medium mb-0">{{ $totalAgents }} <span class="text-muted"> Agents</span></h5>
                                  </div>
                             </div>
                        </div>
                        <div class="col-lg-6">
                             <div class="text-md-end mt-3 mt-md-0">
                                  <button type="button" class="btn btn-outline-primary me-1"><i class="ri-filter-line me-1"></i> Filters</button>
                                  <a href="{{ route('admin.agent.create') }}" class="btn btn-success me-1">
                                    <i class="ri-add-line"></i> New Agent
                                  </a>
                             </div>
                        </div>
                   </div>
              </div>
         </div>
    </div>
</div>

<div class="row">
    @foreach ($agents as $agent)
    <div class="col-xl-4 col-lg-6">
         <div class="card">
              <div class="card-body">
                   <div class="d-flex flex-wrap align-items-center gap-2 border-bottom pb-3">
                        @if($agent->profile_photo)<img src="{{ \App\Support\MediaStorage::url($agent->profile_photo) }}" alt="{{ $agent->name }}" class="avatar-lg rounded-3 border border-light border-3">@else<span class="avatar-lg rounded-3 border border-light border-3 bg-info-subtle text-info d-inline-flex align-items-center justify-content-center"><iconify-icon icon="solar:user-id-bold-duotone" class="fs-32"></iconify-icon></span>@endif
                        <div class="d-block">
                             <a href="{{ route('admin.agent.show', $agent->id) }}" class="text-dark fw-medium fs-16">{{ $agent->name }}</a>
                             <p class="mb-0">{{ $agent->email }}</p>
                             <p class="mb-0 text-primary">{{ $loop->iteration }}</p>
                        </div>
                        <div class="ms-auto">
                           <a href="{{ route('admin.agent.edit', $agent->id) }}" title="Edit Agent">
                                <button type="button" class="btn btn-dark avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-light">
                                    <iconify-icon icon="solar:pen-new-square-broken"></iconify-icon>
                                </button>
                           </a>
                        </div>
                   </div>

                   <div class="border rounded-3 px-3 py-2 bg-light d-flex justify-content-between text-muted small mt-3">
    <div class="d-flex align-items-center">
        <i class="ri-home-4-line me-1"></i> 6 Properties Rented
    </div>
    <div class="d-flex align-items-center">
        <i class="ri-check-double-line me-1"></i> 3 TruCheck™
    </div>
    <div class="d-flex align-items-center">
        <i class="ri-user-shared-line me-1"></i> 2 Checked
    </div>
</div>

              </div>
              <div class="card-footer border-top">
                   <div class="d-flex flex-wrap gap-2">
    <a href="tel:{{ $agent->phone }}" class="btn btn-primary flex-fill">
        <iconify-icon icon="solar:outgoing-call-rounded-broken" class="align-middle fs-18"></iconify-icon>
        Call
    </a>

    <a href="https://wa.me/{{ $agent->phone }}" target="_blank" class="btn btn-outline-success flex-fill">
        <i class="ri-whatsapp-line me-1"></i>WhatsApp
    </a>
</div>
              </div>
         </div>
    </div>
    @endforeach
</div>

<div class="p-3 border-top">
    <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-end mb-0">
            @if ($agents->onFirstPage())
                <li class="page-item disabled"><span class="page-link">Previous</span></li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $agents->previousPageUrl() . '&per_page=' . request('per_page') }}">Previous</a>
                </li>
            @endif

            @foreach ($agents->getUrlRange(1, $agents->lastPage()) as $page => $url)
                <li class="page-item {{ $page == $agents->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url . '&per_page=' . request('per_page') }}">{{ $page }}</a>
                </li>
            @endforeach

            @if ($agents->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $agents->nextPageUrl() . '&per_page=' . request('per_page') }}">Next</a>
                </li>
            @else
                <li class="page-item disabled"><span class="page-link">Next</span></li>
            @endif
        </ul>
    </nav>
</div>

@endsection
