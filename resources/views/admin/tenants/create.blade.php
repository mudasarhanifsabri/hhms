@extends('layouts.app')

@section('content')
    @include('admin.shared.role-create', [
        'roleTitle' => 'Tenant',
        'backLabel' => 'Tenants',
        'backUrl' => route('admin.tenant.index'),
        'storeRoute' => route('admin.tenant.store'),
    ])
@endsection
