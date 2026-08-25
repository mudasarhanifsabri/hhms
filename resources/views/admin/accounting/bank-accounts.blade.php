@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Bank & Cash Management</h4>
        <div class="d-flex gap-2"><a class="btn btn-outline-dark" href="{{ url('/admin/accounting/bank-accounts/statements') }}"><i class="ri-file-list-3-line me-1"></i>All Statements</a><button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#transferModal"><i class="ri-arrow-left-right-line me-1"></i>Transfer</button><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bankModal"><i class="ri-add-line me-1"></i>Add Bank/Cash</button></div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Name</th><th>Type</th><th>Bank</th><th>Account No.</th><th>IBAN</th><th>Opening</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
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
                    <td><span class="badge {{ $bankAccount->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $bankAccount->is_active ? 'Active' : 'Inactive' }}</span></td><td><div class="d-flex gap-1"><a class="btn btn-sm btn-dark" href="{{ url('/admin/accounting/bank-accounts/'.$bankAccount->id.'/statement') }}">Statement</a><button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#editBank-{{ $bankAccount->id }}">Edit</button></div></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No bank or cash accounts yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($bankAccounts as $bankAccount)
<div class="modal fade" id="editBank-{{ $bankAccount->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="post" action="{{ route('admin.accounting.bank-accounts.update',$bankAccount) }}">@csrf @method('PUT')
<div class="modal-header"><h5 class="modal-title">Edit {{ $bankAccount->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3">
<div class="col-md-6"><label class="form-label">Display Name</label><input name="name" class="form-control" value="{{ $bankAccount->name }}" required></div><div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select">@foreach(['bank'=>'Bank','cash'=>'Cash','credit_card'=>'Credit Card','wallet'=>'Wallet'] as $value=>$label)<option value="{{ $value }}" @selected($bankAccount->type===$value)>{{ $label }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Currency</label><input name="currency" value="{{ $bankAccount->currency }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Linked Chart Account</label><select name="accounting_account_id" class="form-select"><option value="">None</option>@foreach($accounts->where('is_bank_cash',true) as $chart)<option value="{{ $chart->id }}" @selected($bankAccount->accounting_account_id===$chart->id)>{{ $chart->code }} - {{ $chart->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Bank Name</label><input name="bank_name" value="{{ $bankAccount->bank_name }}" class="form-control"></div><div class="col-md-6"><label class="form-label">Account Number</label><input name="account_number" value="{{ $bankAccount->account_number }}" class="form-control"></div><div class="col-md-6"><label class="form-label">IBAN</label><input name="iban" value="{{ $bankAccount->iban }}" class="form-control"></div><div class="col-md-4"><label class="form-label">Opening Balance</label><input type="number" step="0.01" name="opening_balance" value="{{ $bankAccount->opening_balance }}" class="form-control" required></div><div class="col-md-8"><label class="form-label">Notes</label><input name="notes" value="{{ $bankAccount->notes }}" class="form-control"></div><div class="col-12 form-check ms-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="active-{{ $bankAccount->id }}" @checked($bankAccount->is_active)><label for="active-{{ $bankAccount->id }}" class="form-check-label">Active account</label></div>
</div><div class="modal-footer"><button class="btn btn-primary">Update Account</button></div></form></div></div>
@endforeach

<div class="modal fade" id="transferModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('admin.accounting.bank-accounts.transfer') }}">@csrf
<div class="modal-header"><h5 class="modal-title">Transfer Between Accounts</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3"><div class="col-12"><div class="alert alert-info py-2 mb-0">This posts balanced debit and credit ledger entries under one transfer number.</div></div><div class="col-12"><label class="form-label">Transfer Date</label><input type="date" name="transfer_date" value="{{ today()->toDateString() }}" class="form-control" required></div><div class="col-12"><label class="form-label">From Account</label><select name="from_account_id" class="form-select" required><option value="">Select source</option>@foreach($bankAccounts->where('is_active',true) as $item)<option value="{{ $item->id }}">{{ $item->name }} · {{ $item->currency }} {{ number_format((float)$item->current_balance,2) }}</option>@endforeach</select></div><div class="col-12"><label class="form-label">To Account</label><select name="to_account_id" class="form-select" required><option value="">Select destination</option>@foreach($bankAccounts->where('is_active',true) as $item)<option value="{{ $item->id }}">{{ $item->name }} · {{ $item->currency }}</option>@endforeach</select></div><div class="col-12"><label class="form-label">Amount</label><input type="number" min="0.01" step="0.01" name="amount" class="form-control" required></div><div class="col-12"><label class="form-label">External Reference</label><input name="reference" class="form-control"></div><div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div></div><div class="modal-footer"><button class="btn btn-success">Post Transfer</button></div></form></div></div>

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
