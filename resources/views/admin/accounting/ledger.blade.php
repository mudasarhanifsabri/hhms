@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Accounting Ledger</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#entryModal"><i class="ri-add-line me-1"></i>Add Entry</button>
    </div>
    <div class="card-body border-bottom">
        <form class="row g-2">
            <div class="col-md-2"><select name="type" class="form-select"><option value="">All Types</option>@foreach($entryTypes as $key => $label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="property_id" class="form-select"><option value="">All Units</option>@foreach($properties as $property)<option value="{{ $property->id }}" @selected(request('property_id')===$property->id)>{{ $property->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
            <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-soft-primary w-100">Filter</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Date</th><th>Entry No</th><th>Account</th><th>Type</th><th>Unit</th><th>Description</th><th>VAT</th><th>Debit</th><th>Credit</th></tr></thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                    <td class="fw-semibold">{{ $entry->entry_no }}</td>
                    <td>{{ $entry->accountingAccount?->code }} {{ $entry->accountingAccount?->name }}</td>
                    <td>{{ $entryTypes[$entry->type] ?? ucfirst($entry->type) }}</td>
                    <td>{{ $entry->property?->name ?? '-' }}</td>
                    <td>{{ Str::limit($entry->description, 55) }}</td>
                    <td>AED {{ number_format((float) $entry->vat_amount, 2) }}</td>
                    <td>AED {{ number_format((float) $entry->debit, 2) }}</td>
                    <td>AED {{ number_format((float) $entry->credit, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No entries found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $entries->links('pagination::bootstrap-5') }}</div>
</div>

<div class="modal fade" id="entryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="post" action="{{ route('admin.accounting.ledger.store') }}" enctype="multipart/form-data">@csrf
        <div class="modal-header"><h5 class="modal-title">Post Accounting Entry</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="entry_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Type</label><select name="type" class="form-select" required>@foreach($entryTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Category</label><input name="category" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Chart Account</label><select name="accounting_account_id" class="form-select"><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Paid From / Bank</label><select name="paid_from_account_id" class="form-select"><option value="">None</option>@foreach($bankAccounts as $bankAccount)<option value="{{ $bankAccount->id }}">{{ $bankAccount->name }} ({{ ucfirst($bankAccount->type) }})</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Unit</label><select name="property_id" class="form-select"><option value="">None</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Owner</label><select name="landlord_id" class="form-select"><option value="">None</option>@foreach($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Debit</label><input type="number" step="0.01" name="debit" class="form-control" value="0"></div>
            <div class="col-md-4"><label class="form-label">Credit</label><input type="number" step="0.01" name="credit" class="form-control" value="0"></div>
            <div class="col-md-4"><label class="form-label">VAT %</label><input type="number" step="0.01" name="vat_rate" class="form-control" value="5"></div>
            <div class="col-md-6"><label class="form-label">Payment Method</label><input name="payment_method" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Reference</label><input name="transaction_reference" class="form-control"></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
            <div class="col-12"><label class="form-label">Attachment</label><input type="file" name="attachment" class="form-control"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Post Entry</button></div>
    </form></div>
</div>
@endsection
