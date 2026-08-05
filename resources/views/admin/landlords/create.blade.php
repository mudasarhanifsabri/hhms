@extends('layouts.app')

@section('content')
    @include('admin.shared.role-create', [
        'roleTitle' => 'Landlord',
        'backLabel' => 'Landlords',
        'backUrl' => route('admin.landlord.index'),
        'storeRoute' => route('admin.landlord.store'),
    ])
@endsection
