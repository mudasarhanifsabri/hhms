@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Bank & Cash Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bankModal"><i class="ri-add-line me-1"></i>Add Bank/Cash</button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Name</th><th>Type</th><th>Bank</th><th>Account No.</th><th>IBAN</th><th>Opening</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($bankAccounts as $bankAccount)
                <tr>
                    <td class="fw-semibold">{{ $bankAccount->name }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $bankAccount->type)) }}</td>
                    <td>{{ $bankAccount->bank_name ?? '-' }}</td>
                    <td>{{ $bankAccount->account_number ?? '-' }}</td>
                    <td>{{ $bankAccount->iban ?? '-' }}</td>
                    <td>AED {{ number_format((float) $bankAccount->opening_balance, 2) }}</td>
                    <td>AED {{ number_format((float) $bankAccount->current_balance, 2) }}</td>
                    <td><span class="badge {{ $bankAccount->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $bankAccount->is_active ? 'Active' : 'Inactive' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No bank or cash accounts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="bankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form class="modal-content" method="post" action="{{ route('admin.accounting.bank-accounts.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Add Bank / Cash Account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-6"><label class="form-label">Display Name</label><input name="name" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select"><option value="bank">Bank</option><option value="cash">Cash</option><option value="credit_card">Credit Card</option><option value="wallet">Wallet</option></select></div>
            <div class="col-md-3"><label class="form-label">Currency</label><input name="currency" class="form-control" value="AED"></div>
            <div class="col-md-6"><label class="form-label">Linked Chart Account</label><select name="accounting_account_id" class="form-select"><option value="">None</option>@foreach($accounts->where('is_bank_cash', true) as $account)<option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Bank Name</label><input name="bank_name" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Account Number</label><input name="account_number" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">IBAN</label><input name="iban" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" class="form-control" value="0"></div>
            <div class="col-md-8"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save Account</button></div>
    </form></div>
</div>
@endsection
