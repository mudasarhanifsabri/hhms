@extends('layouts.app')

@section('content')
    @include('admin.shared.role-create', [
        'roleTitle' => 'Agent',
        'backLabel' => 'Agents',
        'backUrl' => route('admin.agent.index'),
        'storeRoute' => route('admin.agent.store'),
    ])
@endsection
