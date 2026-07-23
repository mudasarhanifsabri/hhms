@extends('layouts.app')

@section('title', 'Owned Properties')

@section('content')
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
                'detailsRoute' => $detailsRoute,
                'accountStatementRoute' => $accountStatementRoute,
                'ownedPropertiesRoute' => route('admin.landlord.owned-properties', $landlord->id),
            ])

            @include('admin.landlords.partials.owned-properties-grid')
        </div>
    </div>
@endsection
