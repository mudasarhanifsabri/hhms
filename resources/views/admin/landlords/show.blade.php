@extends('layouts.app')

@section('title', 'Landlord Profile')

@section('content')
    @include('admin.landlords.partials.profile-tabs', [
        'detailsRoute' => route('admin.landlord.show', $landlord->id),
        'accountStatementRoute' => route('admin.landlord.account-statement', $landlord->id),
        'ownedPropertiesRoute' => route('admin.landlord.owned-properties', $landlord->id),
        'securityRoute' => Route::has('admin.landlord.security') ? route('admin.landlord.security', $landlord->id) : null,
    ])
    @include('admin.shared.user-profile')
@endsection
