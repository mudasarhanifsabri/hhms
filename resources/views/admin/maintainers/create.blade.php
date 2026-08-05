@extends('layouts.app')

@section('content')
    @include('admin.shared.role-create', [
        'roleTitle' => 'Maintainer',
        'backLabel' => 'Maintainers',
        'backUrl' => route('admin.maintainer.index'),
        'storeRoute' => route('admin.maintainer.store'),
    ])
@endsection
