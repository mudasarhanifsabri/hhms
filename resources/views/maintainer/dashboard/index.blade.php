@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <a href="{{ route('maintainer.task.index') }}" class="card text-decoration-none">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-1 text-dark">My Tasks</h4>
                        <p class="text-muted mb-0">Open assigned work</p>
                    </div>
                    <iconify-icon icon="solar:clipboard-list-bold" class="fs-32 text-primary"></iconify-icon>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('maintainer.task.grid') }}" class="card text-decoration-none">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-1 text-dark">Grid View</h4>
                        <p class="text-muted mb-0">Scan task cards</p>
                    </div>
                    <iconify-icon icon="solar:widget-5-bold" class="fs-32 text-success"></iconify-icon>
                </div>
            </a>
        </div>
    </div>
    @include('maintainer.partials.mobile-nav')
@endsection
