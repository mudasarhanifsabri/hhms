@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
    <form class="d-flex gap-2 align-items-end">
        <div><label class="form-label">Month</label><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control"></div>
        <div><label class="form-label">Unit</label><select name="property_id" class="form-select"><option value="">All Units</option>@foreach($properties as $property)<option value="{{ $property->id }}" @selected(request('property_id')===$property->id)>{{ $property->name }}</option>@endforeach</select></div>
        <button class="btn btn-soft-primary">Apply</button>
    </form>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#utilityAccountModal"><i class="ri-settings-3-line me-1"></i>Utility Account</button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#utilityBillModal"><i class="ri-add-line me-1"></i>Record Utilities</button>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">Monthly Utility Register - {{ $month->format('F Y') }}</h4></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle">
                <tr><th>Unit Number</th>@foreach($utilityTypes as $key => $label)<th class="utility-cell">{{ $label }}</th>@endforeach</tr>
            </thead>
            <tbody>
            @forelse($properties as $property)
                <tr>
                    <td>
                        <strong>{{ $property->name }}</strong>
                        <p class="text-muted mb-0">{{ $property->building?->name }}</p>
                    </td>
                    @foreach($utilityTypes as $key => $label)
                        @php($account = $property->utilityAccounts->firstWhere('utility_type', $key))
                        @php($bill = $bills->get($property->id.'|'.$key))
                        <td>
                            @if($bill)
                                <div class="fw-semibold">AED {{ number_format((float) $bill->total_amount, 2) }}</div>
                                <span class="badge {{ in_array($bill->status, ['paid','owner_paid']) ? 'bg-success' : 'bg-warning' }}">{{ $bill->status_label }}</span>
                                @if(! in_array($bill->status, ['paid','owner_paid']))
                                    <button class="btn btn-xs btn-soft-success mt-1" data-bs-toggle="modal" data-bs-target="#payBill{{ $bill->id }}">Pay</button>
                                @endif
                            @elseif($account)
                                <span class="badge bg-light text-dark">{{ $account->responsibility_label }}</span>
                                <p class="text-muted mb-0 small">{{ $account->supplier ?: 'Ready to record' }}</p>
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No units available.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($bills as $bill)
    @if(! in_array($bill->status, ['paid','owner_paid']))
        <div class="modal fade" id="payBill{{ $bill->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('admin.accounting.utilities.bills.pay', $bill->id) }}" enctype="multipart/form-data">@csrf
                <div class="modal-header"><h5 class="modal-title">Pay {{ $bill->account?->type_label }} Bill</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12 alert alert-info mb-0">Unit: {{ $bill->property?->name }} | Total: AED {{ number_format((float) $bill->total_amount, 2) }}</div>
                    <div class="col-md-6"><label class="form-label">Paid Date</label><input type="date" name="paid_at" value="{{ now()->toDateString() }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Payment Method</label><input name="payment_method" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Transaction Reference</label><input name="transaction_reference" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Receipt</label><input type="file" name="receipt" class="form-control"></div>
                    <div class="col-12"><div class="form-check"><input type="checkbox" name="owner_paid" value="1" id="ownerPaid{{ $bill->id }}" class="form-check-input"><label for="ownerPaid{{ $bill->id }}" class="form-check-label">Owner paid directly, do not create company expense</label></div></div>
                </div>
                <div class="modal-footer"><button class="btn btn-success">Save Payment</button></div>
            </form></div>
        </div>
    @endif
@endforeach

<div class="modal fade" id="utilityAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="post" action="{{ route('admin.accounting.utilities.accounts.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Utility Account Setup</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-6"><label class="form-label">Unit</label><select name="property_id" class="form-select" required>@foreach($properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Utility</label><select name="utility_type" class="form-select" required>@foreach($utilityTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Responsibility</label><select name="responsibility" class="form-select" required>@foreach($responsibilities as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Supplier</label><input name="supplier" class="form-control" placeholder="DEWA, Du, Empower"></div>
            <div class="col-md-4"><label class="form-label">Account No.</label><input name="account_number" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Contract No.</label><input name="contract_number" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Portal Username</label><input name="username" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Portal Password</label><input type="password" name="portal_password" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Status</label><select name="connection_status" class="form-select"><option value="active">Active</option><option value="pending">Pending</option><option value="disconnected">Disconnected</option></select></div>
            <div class="col-md-4"><label class="form-label">Start Date</label><input type="date" name="connection_start_date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Expiry Date</label><input type="date" name="contract_expiry_date" class="form-control"></div>
            <div class="col-md-3"><label class="form-label">Billing Day</label><input type="number" min="1" max="31" name="billing_day" class="form-control"></div>
            <div class="col-md-9"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Save Account</button></div>
    </form></div>
</div>

<div class="modal fade" id="utilityBillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form class="modal-content" method="post" action="{{ route('admin.accounting.utilities.bills.store') }}">@csrf
        <div class="modal-header"><h5 class="modal-title">Record Utility Bill</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
            <div class="col-md-6"><label class="form-label">Utility Account</label><select name="utility_account_id" class="form-select" required>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->property?->name }} - {{ $account->type_label }} ({{ $account->responsibility_label }})</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Bill Month</label><input type="month" name="bill_month" value="{{ $month->format('Y-m') }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Bill Date</label><input type="date" name="bill_date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Net Amount</label><input type="number" step="0.01" name="bill_amount" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">VAT %</label><input type="number" step="0.01" name="vat_rate" value="5" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Booking Link</label><select name="booking_id" class="form-select"><option value="">No booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}">{{ $booking->booking_reference }} - {{ $booking->guest_name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
        </div>
        <div class="modal-footer"><button class="btn btn-primary">Record Bill</button></div>
    </form></div>
</div>
@endsection
