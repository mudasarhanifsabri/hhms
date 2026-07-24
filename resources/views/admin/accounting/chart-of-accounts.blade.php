@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Chart of Accounts</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#accountModal"><i class="ri-add-line me-1"></i>Add Account</button>
    </div>
    <div class="card-body">
        @foreach($accountTypes as $type => $label)
            <div class="mb-4">
                <h5 class="mb-2">{{ $label }}</h5>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="bg-light-subtle"><tr><th>Code</th><th>Account Name</th><th>Parent</th><th>Bank/Cash</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($accounts->get($type, collect()) as $account)
                            <tr>
                                <td class="fw-semibold">{{ $account->code }}</td>
                                <td>{{ $account->name }}</td>
                                <td>{{ $account->parent_code ?? '-' }}</td>
                                <td>{{ $account->is_bank_cash ? 'Yes' : 'No' }}</td>
                                <td><span class="badge {{ $account->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">No {{ strtolower($label) }} accounts.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="modal fade" id="accountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('admin.accounting.chart-of-accounts.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Add Chart Account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-4"><label class="form-label">Code</label><input name="code" class="form-control" required></div>
            <div class="col-md-8"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">Type</label><select name="type" class="form-select" required>@foreach($accountTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Parent Code</label><input name="parent_code" class="form-control"></div>
            <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="is_bank_cash" value="1" class="form-check-input" id="bankCash"><label for="bankCash" class="form-check-label">This account is bank/cash related</label></div></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save Account</button></div>
    </form></div>
</div>
@endsection
