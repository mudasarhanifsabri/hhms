@extends('layouts.app')

@section('title', 'Owner Account Statement')

@section('content')
    @if($unitContext)
        @include('admin.properties.partials.unit-tabs', ['property' => $unitContext])
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="text-uppercase text-primary fw-bold small mb-1">Unit Financial Workspace</div>
                    <h3 class="fw-semibold mb-1">Unit {{ $unitContext->name }} <span class="text-muted fw-normal">|</span> {{ $unitContext->building?->building_name ?? $unitContext->building?->name ?? 'No Building' }}</h3>
                    <span class="text-muted">Account statement for {{ $landlord->name }}</span>
                </div>
                <a href="{{ route('admin.property.show', $unitContext) }}" class="btn btn-light"><i class="ri-arrow-left-line me-1"></i>Back to Unit</a>
            </div>
        </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $landlord->name }}</h3>
                        <span class="badge bg-info text-white fs-12 px-2 py-1">Owner / Landlord</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="mailto:{{ $landlord->email }}" class="btn btn-outline-primary">
                            <i class="ri-mail-fill me-1"></i>Email
                        </a>
                        <a href="{{ $backRoute }}" class="btn btn-light">
                            <i class="ri-arrow-left-line me-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>

            @include('admin.landlords.partials.profile-tabs', [
                'accountStatementRoute' => route('admin.landlord.account-statement', $landlord->id),
                'detailsRoute' => $detailsRoute,
                'ownedPropertiesRoute' => $ownedPropertiesRoute,
            ])

            @include('admin.landlords.partials.account-statement-card')
        </div>
    </div>
    @endif
    @if($unitContext)
        @include('admin.landlords.partials.account-statement-card')
    @endif
@endsection
