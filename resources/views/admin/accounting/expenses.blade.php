@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Expense Management</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.accounting.expenses.import') }}" class="btn btn-soft-primary"><i class="ri-upload-cloud-2-line me-1"></i>Import Expenses</a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expenseModal"><i class="ri-add-line me-1"></i>Record Expense</button>
        </div>
    </div>
    <div class="card-body border-bottom">
        <form class="row g-2">
            <div class="col-md-3"><select name="category" class="form-select"><option value="">All Categories</option>@foreach($expenseCategories as $key => $label)<option value="{{ $key }}" @selected(request('category')===$key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="property_id" class="form-select"><option value="">All Units</option>@foreach($properties as $property)<option value="{{ $property->id }}" @selected(request('property_id')===$property->id)>{{ $property->name }}</option>@endforeach</select></div>
            <div class="col-md-3">
                <select name="approval_status" class="form-select">
                    <option value="">All Status</option>
                    @foreach(['draft' => 'Draft', 'pending' => 'Pending', 'reviewed' => 'Reviewed', 'approved' => 'Approved', 'paid' => 'Paid', 'rejected' => 'Rejected'] as $key => $label)
                        <option value="{{ $key }}" @selected(request('approval_status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-soft-primary w-100">Filter</button></div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Date</th><th>No.</th><th>Category</th><th>Unit</th><th>Vendor</th><th>Paid From</th><th>Status</th><th>VAT</th><th>Total</th><th>Files</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td>{{ $expense->expense_date?->format('d M Y') }}</td>
                    <td class="fw-semibold">{{ $expense->expense_no }}</td>
                    <td>{{ $expenseCategories[$expense->category] ?? ucfirst($expense->category) }}</td>
                    <td>{{ $expense->property?->name ?? '-' }}</td>
                    <td>{{ $expense->vendor?->name ?? $expense->supplier ?? '-' }}</td>
                    <td>{{ $expense->paidFromAccount?->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ in_array($expense->approval_status, ['approved', 'paid'], true) ? 'bg-success' : ($expense->approval_status === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                            {{ ucfirst(str_replace('_', ' ', $expense->approval_status)) }}
                        </span>
                        @if($expense->needs_review)
                            <span class="badge bg-danger-subtle text-danger border ms-1">Needs Review</span>
                        @endif
                    </td>
                    <td>AED {{ number_format((float) $expense->vat_amount, 2) }}</td>
                    <td>AED {{ number_format((float) $expense->gross_amount, 2) }}</td>
                    <td>
                        @if($expense->receipt_path)<a href="{{ \App\Support\MediaStorage::url($expense->receipt_path) }}" target="_blank" class="btn btn-sm btn-soft-primary" title="Receipt"><i class="ri-receipt-line"></i></a>@endif
                        @if($expense->invoice_path)<a href="{{ \App\Support\MediaStorage::url($expense->invoice_path) }}" target="_blank" class="btn btn-sm btn-soft-info" title="Invoice"><i class="ri-file-list-3-line"></i></a>@endif
                        @if($expense->import_source_file)<a href="{{ \App\Support\MediaStorage::url($expense->import_source_file) }}" target="_blank" class="btn btn-sm btn-soft-secondary" title="Import Source"><i class="ri-file-upload-line"></i></a>@endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editExpense{{ $expense->id }}"><i class="ri-edit-line"></i></button>
                        @if(! in_array($expense->approval_status, ['approved', 'paid', 'rejected'], true))
                            <form action="{{ route('admin.accounting.expenses.approve', $expense->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success" title="Approve and post"><i class="ri-check-line"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted py-4">No expenses recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $expenses->links('pagination::bootstrap-5') }}</div>
</div>

<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="post" action="{{ route('admin.accounting.expenses.store') }}" enctype="multipart/form-data">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Expense</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="expense_date" value="{{ now()->toDateString() }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select" required>@foreach($expenseCategories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select"><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Supplier Text</label><input name="supplier" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Unit</label><select name="property_id" class="form-select"><option value="">General Company Expense</option>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Booking</label><select name="booking_id" class="form-select"><option value="">No Booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}">{{ $booking->booking_reference }} - {{ $booking->guest_name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Paid / Charged To</label><select name="responsibility" class="form-select" required><option value="company">Company</option><option value="owner">Owner</option><option value="tenant_guest">Tenant / Guest</option></select></div>
            <div class="col-md-4"><label class="form-label">Paid From Account</label><select name="paid_from_account_id" class="form-select"><option value="">Select bank/cash</option>@foreach($bankAccounts as $bankAccount)<option value="{{ $bankAccount->id }}">{{ $bankAccount->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Approval Status</label><select name="approval_status" class="form-select"><option value="draft">Draft</option><option value="pending">Pending</option><option value="reviewed">Reviewed</option><option value="approved" selected>Approved</option><option value="paid">Paid</option><option value="rejected">Rejected</option></select></div>
            <div class="col-md-4"><label class="form-label">Amount</label><input type="number" step="0.01" name="net_amount" class="form-control" required><small class="text-muted">Enter bill amount.</small></div>
            <div class="col-md-4"><label class="form-label">VAT %</label><input type="number" step="0.01" name="vat_rate" value="5" class="form-control"></div>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="vat_included" value="1" id="expenseVatIncluded"><label class="form-check-label" for="expenseVatIncluded">VAT included in amount</label></div></div>
            <div class="col-md-6"><label class="form-label">Payment Method</label><input name="payment_method" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Transaction Reference</label><input name="transaction_reference" class="form-control"></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="owner_billable" value="1" id="ownerBillable"><label class="form-check-label" for="ownerBillable">Add this expense to owner statement</label></div></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
            <div class="col-md-6"><label class="form-label">Receipt</label><input type="file" name="receipt" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Supplier Invoice</label><input type="file" name="invoice" class="form-control"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save Expense</button></div>
    </form></div>
</div>

@foreach($expenses as $expense)
<div class="modal fade" id="editExpense{{ $expense->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="post" action="{{ route('admin.accounting.expenses.update', $expense->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Edit Expense {{ $expense->expense_no }}</h5>
                    @if($expense->imported_transaction_id)
                        <small class="text-muted">Imported ID: {{ $expense->imported_transaction_id }}</small>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="expense_date" value="{{ $expense->expense_date?->toDateString() }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select" required>@foreach($expenseCategories as $key => $label)<option value="{{ $key }}" @selected($expense->category === $key)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select"><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected($expense->vendor_id === $vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Supplier Text</label><input name="supplier" value="{{ $expense->supplier }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Unit</label><select name="property_id" class="form-select"><option value="">General Company Expense</option>@foreach($properties as $property)<option value="{{ $property->id }}" @selected($expense->property_id === $property->id)>{{ $property->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Booking</label><select name="booking_id" class="form-select"><option value="">No Booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}" @selected($expense->booking_id === $booking->id)>{{ $booking->booking_reference }} - {{ $booking->guest_name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Paid / Charged To</label><select name="responsibility" class="form-select" required><option value="company" @selected($expense->responsibility === 'company')>Company</option><option value="owner" @selected($expense->responsibility === 'owner')>Owner</option><option value="tenant_guest" @selected($expense->responsibility === 'tenant_guest')>Tenant / Guest</option></select></div>
                <div class="col-md-4"><label class="form-label">Paid From Account</label><select name="paid_from_account_id" class="form-select"><option value="">Select bank/cash</option>@foreach($bankAccounts as $bankAccount)<option value="{{ $bankAccount->id }}" @selected($expense->paid_from_account_id === $bankAccount->id)>{{ $bankAccount->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Status</label><select name="approval_status" class="form-select"><option value="draft" @selected($expense->approval_status === 'draft')>Draft</option><option value="pending" @selected($expense->approval_status === 'pending')>Pending</option><option value="reviewed" @selected($expense->approval_status === 'reviewed')>Reviewed</option><option value="approved" @selected($expense->approval_status === 'approved')>Approved</option><option value="paid" @selected($expense->approval_status === 'paid')>Paid</option><option value="rejected" @selected($expense->approval_status === 'rejected')>Rejected</option></select></div>
                <div class="col-md-4"><label class="form-label">Amount</label><input type="number" step="0.01" name="net_amount" value="{{ $expense->gross_amount ?: $expense->net_amount }}" class="form-control" required><small class="text-muted">Use checkbox if this amount already includes VAT.</small></div>
                <div class="col-md-4"><label class="form-label">VAT %</label><input type="number" step="0.01" name="vat_rate" value="{{ $expense->vat_rate }}" class="form-control"></div>
                <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="vat_included" value="1" id="vatIncluded{{ $expense->id }}" checked><label class="form-check-label" for="vatIncluded{{ $expense->id }}">VAT included in amount</label></div></div>
                <div class="col-md-4"><label class="form-label">Transaction Reference</label><input name="transaction_reference" value="{{ $expense->transaction_reference }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Payment Method</label><input name="payment_method" value="{{ $expense->payment_method }}" class="form-control"></div>
                <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="owner_billable" value="1" id="ownerBillable{{ $expense->id }}" @checked($expense->owner_billable)><label class="form-check-label" for="ownerBillable{{ $expense->id }}">Add to owner statement after approval</label></div></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control">{{ $expense->description }}</textarea></div>
                <div class="col-md-6"><label class="form-label">Receipt</label><input type="file" name="receipt" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">Supplier Invoice</label><input type="file" name="invoice" class="form-control"></div>
                @if($expense->imported_payload)
                    <div class="col-12">
                        <div class="alert alert-light border mb-0">
                            <strong>Original imported data is kept for audit.</strong>
                            <pre class="small mb-0 mt-2" style="white-space:pre-wrap;max-height:160px;overflow:auto">{{ json_encode($expense->imported_payload, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Save Changes</button></div>
        </form>
    </div>
</div>
@endforeach
@endsection
