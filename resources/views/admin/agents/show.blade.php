@extends('layouts.app')

@section('title', 'Agent Profile')

@section('content')
    <div class="card"><div class="card-body"><h4>Agency fee commission</h4><p class="text-muted">Default percentage of the agency fee paid to this agent. New bookings copy this rate; management may override it before payment.</p><form method="POST" action="{{ route('admin.agent.commission', $agent) }}" class="d-flex gap-2 align-items-end">@csrf @method('PUT')<div><label class="form-label" for="agentCommission">Commission %</label><input class="form-control" id="agentCommission" name="agent_commission" type="number" min="0" max="100" step="0.01" value="{{ old('agent_commission', $agent->agent_commission) }}" required></div><button class="btn btn-primary">Save default rate</button></form></div></div>
    @include('admin.shared.user-profile')
@endsection
