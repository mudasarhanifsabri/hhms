@extends('layouts.app')
@section('content')
@include('admin.bookings.partials.compact-style')
<div class="booking-workspace">
@php
    $money = fn($v) => 'AED '.number_format((float)$v, 2);
    $depositInvoices = $booking->invoices->filter(fn($i) => (float)(($i->fees ?? [])['Security Deposit'] ?? 0) > 0);
    $openRequest = $refunds->first(fn($r) => in_array($r->status, ['pending', 'approved']));
    $canRefund = !$openRequest && $totals['held'] > 0;
    $canCarry = $canRefund && $booking->renewals->isNotEmpty();
    $charges = $depositInvoices->sum(fn($i) => (float)(($i->fees ?? [])['Security Deposit'] ?? 0));
@endphp
<div class="deposit-wallet">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div><div class="text-uppercase small text-muted mb-1">Booking finances</div><h4 class="mb-1">Security Deposit Wallet</h4>
        <p class="text-muted mb-0">{{ $booking->booking_reference }} · {{ $booking->guest_name }} · {{ $booking->property?->building?->name }} — {{ $booking->property?->name }}</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.booking.show', $booking) }}">← Booking & Invoices</a>
    </div>
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }} Please reopen the action to correct your details and reattach any files.</div>@endif

    @include('admin.bookings.partials.navigation')
    <div class="row g-3 mb-3">
        <div class="col-lg-5"><div class="card h-100 mb-0"><div class="card-header"><h5 class="mb-0">Collect Security Deposit</h5></div><div class="card-body">
            <div class="deposit-metrics">
                <div><small>Required</small><strong>{{ $money($charges) }}</strong></div>
                <div><small>Received</small><strong class="text-success">{{ $money($totals['received']) }}</strong></div>
                <div><small>Unallocated*</small><strong class="text-danger">{{ $money(max(0,$charges-$totals['received'])) }}</strong></div>
            </div>
            <p class="small text-muted mt-3 mb-0">*Invoice deposit charges not yet allocated to this wallet. An existing payment may already cover them.</p>
        </div></div></div>
        <div class="col-lg-7"><div class="card h-100 mb-0"><div class="card-header"><h5 class="mb-0">Security Deposit Wallet</h5></div><div class="card-body">
            <div class="deposit-metrics">
                @foreach(['received'=>'Received','deducted'=>'Deductions','refunded'=>'Refunded','held'=>'Held'] as $key=>$label)
                <div><small>{{ $label }}</small><strong class="{{ $key==='held'?'text-primary':($key==='deducted'?'text-danger':'text-success') }}">{{ $money($totals[$key]) }}</strong></div>
                @endforeach
            </div>
            <div class="d-flex justify-content-between gap-3 mt-3">
                <div><small class="d-block mb-1">Status</small><span class="badge {{ $totals['held']>0?'bg-warning text-dark':'bg-light text-dark' }}">{{ $openRequest ? ucfirst($openRequest->status).' refund' : ($totals['held']>0?'Held':($entries->isEmpty()?'Not received':'No funds held')) }}</span></div>
                <div class="text-end"><small class="d-block mb-1">Linked Invoices</small>@forelse($depositInvoices as $invoice)<a class="d-block" href="{{ route('admin.booking.show',$booking) }}#bookingInvoices">{{ $invoice->invoice_number }}</a>@empty<span>—</span>@endforelse</div>
            </div>
            @if($totals['carry_in']>0 || $totals['carry_out']>0)<p class="small text-muted mt-3 mb-0">Carried in {{ $money($totals['carry_in']) }} · Carried out {{ $money($totals['carry_out']) }}</p>@endif
        </div></div></div>
    </div>
    <div class="card"><div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#collectDeposit">+ Receive Deposit</button>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#allocateDeposit">Allocate Existing Payment</button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestRefund" @disabled(!$canRefund)>Request Refund</button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#carryDeposit" @disabled(!$canCarry)>Carry Forward</button>
        </div>
        <p class="small text-muted mt-3 mb-0">Already received the deposit? Use <strong>Allocate Existing Payment</strong>, not Receive Deposit. A charge is not proof of receipt; deposits are separate from rental income.</p>
        @if($openRequest)<div class="small text-warning mt-2">{{ $openRequest->request_no }} is {{ $openRequest->status }}. Complete this request before requesting another refund or carrying funds forward.</div>
        @elseif(!$canRefund)<div class="small text-muted mt-2">Refund and carry-forward become available when funds are held.</div>
        @elseif(!$canCarry)<div class="small text-muted mt-2">Carry-forward requires a linked renewal for the same guest and unit.</div>@endif
    </div></div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="mb-1">Refund Requests</h5><span class="small text-muted">Review deductions, approve requests and record actual payments.</span></div>
            <label class="d-flex align-items-center gap-2 small">Status
                <select id="refundStatus" class="form-select form-select-sm"><option value="">All statuses</option><option value="pending">Pending</option><option value="approved">Approved</option><option value="settled">Settled</option><option value="rejected">Rejected</option></select>
            </label>
        </div>
        <div class="table-responsive"><table class="table align-middle mb-0" id="refundTable">
            <thead><tr><th>Request / Date</th><th class="text-end">Deductions</th><th class="text-end">Refund</th><th class="text-end">Paid</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>@forelse($refunds as $refund)
                <tr data-status="{{ $refund->status }}">
                    <td><span class="fw-semibold">{{ $refund->request_no }}</span><div class="small text-muted">{{ $refund->created_at?->format('d M Y') }} · {{ $refund->requester?->name }}</div></td>
                    <td class="text-end text-nowrap">{{ $money($refund->deduction_amount) }}</td><td class="text-end text-nowrap">{{ $money($refund->refund_amount) }}</td><td class="text-end text-nowrap">{{ $money($refund->paid_amount) }}</td>
                    <td><span class="badge {{ ['settled'=>'bg-success','rejected'=>'bg-danger','approved'=>'bg-primary','pending'=>'bg-warning text-dark'][$refund->status] ?? 'bg-secondary' }}">{{ ucfirst($refund->status) }}</span></td>
                    <td class="text-end text-nowrap"><button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#refund-{{ $refund->id }}">{{ $refund->status==='pending' ? 'Review' : 'View Details' }}</button>
                    @if($refund->status==='approved')<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#pay-{{ $refund->id }}">Record Refund</button>@endif</td>
                </tr>
            @empty<tr><td colspan="6" class="text-center py-5 text-muted">No refund requests yet. Start with Request Refund when the deposit is ready to return.</td></tr>@endforelse
                <tr id="refundFilterEmpty" hidden><td colspan="6" class="text-center py-4 text-muted">No requests match this status.</td></tr>
            </tbody>
        </table></div>
    </div>
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><h5 class="mb-1">Deposit History & Refund Receipts</h5><span class="small text-muted">{{ $entries->count() }} recorded movements · Full audit trail</span></div>
            <input type="search" id="depositSearch" class="form-control form-control-sm deposit-search" placeholder="Search transactions…" aria-label="Search deposit transactions">
        </div>
        <div class="table-responsive"><table class="table align-middle mb-0" id="depositTable">
            <thead><tr><th>Date</th><th>Activity</th><th class="text-end">Amount</th><th>Account / Reference</th><th>Recorded By</th><th class="text-end">Actions</th></tr></thead>
            <tbody>@forelse($entries as $entry)
                <tr data-entry><td class="text-nowrap">{{ $entry->entry_date?->format('d M Y') }}</td><td><span class="badge {{ in_array($entry->kind,['received','carry_in']) ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst(str_replace('_',' ',$entry->kind)) }}</span></td>
                <td class="text-end text-nowrap fw-semibold">{{ $money($entry->amount) }}</td><td>{{ $entry->bankAccount?->name ?? 'No cash movement' }}<div class="small text-muted">{{ $entry->reference }}</div></td><td>{{ $entry->creator?->name ?? 'System' }}</td>
                <td class="text-end text-nowrap"><button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#entry-{{ $entry->id }}">View</button>
                @if($entry->kind==='refunded')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.booking.deposit.receipt',[$booking,$entry]) }}">Refund Receipt PDF</a>@endif</td></tr>
            @empty<tr><td colspan="6" class="text-center py-5 text-muted">No deposit movements. Record a received deposit or allocate an existing payment to begin.</td></tr>@endforelse
                <tr id="depositFilterEmpty" hidden><td colspan="6" class="text-center py-4 text-muted">No transactions match your search.</td></tr>
            </tbody>
        </table></div>
    </div>
    <div class="card"><div class="card-header"><h5 class="mb-0">Refund Receipt & History — Timeline</h5></div><div class="card-body">
        <ol class="deposit-timeline">
        @forelse($entries as $entry)
            <li><div class="d-flex flex-wrap justify-content-between gap-2"><strong>{{ ['received'=>'Deposit Received','deducted'=>'Deduction Approved','refunded'=>'Refund Paid','carry_in'=>'Deposit Carried In','carry_out'=>'Deposit Carried Out'][$entry->kind] ?? ucfirst($entry->kind) }}</strong><strong class="{{ in_array($entry->kind,['received','carry_in'])?'text-success':'text-danger' }}">{{ in_array($entry->kind,['received','carry_in'])?'+':'−' }} {{ $money($entry->amount) }}</strong></div>
                <div class="small text-muted mt-2">{{ $entry->entry_date?->format('d M Y') }} · by {{ $entry->creator?->name ?? 'System' }} · Ref: {{ $entry->reference ?? '—' }}</div>
                <button type="button" class="btn btn-sm btn-link px-0" data-bs-toggle="modal" data-bs-target="#entry-{{ $entry->id }}">View Audit Log</button>
                @if($entry->kind==='refunded')<a class="btn btn-sm btn-outline-primary ms-2" href="{{ route('admin.booking.deposit.receipt',[$booking,$entry]) }}">Download Refund Receipt</a>@endif
            </li>
        @empty<li class="text-muted">Your deposit activity will appear here.</li>@endforelse
        </ol>
        <div class="deposit-notice">Original invoice charges remain unchanged by refunds.</div>
    </div></div>
    <p class="small text-muted text-center">Approval does not transfer money. Record Refund only after the payment has actually been made.</p>
</div>
<div class="modal fade deposit-modal" id="allocateDeposit" tabindex="-1" aria-labelledby="allocateDeposit-title" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title" id="allocateDeposit-title">Allocate Existing Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
 <div class="modal-body"><form method="POST" action="{{ route('admin.booking.deposit.allocate',$booking) }}">@csrf<input type="hidden" name="submission_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
<label>Recorded payment</label><select name="payment_id" class="form-select mb-2" required><option value="">Select payment</option>@foreach($depositInvoices as $invoice)@foreach($invoice->payments as $payment)@if((float)$payment->amount>(float)($allocations[$payment->id]??0))<option value="{{ $payment->id }}">{{ $invoice->invoice_number }} · {{ $payment->payment_date?->format('d M Y') }} · Paid {{ $money($payment->amount) }} · Deposit allocated {{ $money($allocations[$payment->id]??0) }}</option>@endif @endforeach @endforeach</select>
<label>Deposit portion (AED)</label><input type="number" name="amount" class="form-control mb-3" min="0.01" step="0.01" required><button class="btn btn-primary">Allocate — No New Collection</button><p class="small text-muted mt-2">Legacy “Paid” invoices without an itemised ledger payment must be reconciled before refunding.</p></form></div></div></div></div><div class="modal fade deposit-modal" id="collectDeposit" tabindex="-1" aria-labelledby="collectDeposit-title" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title" id="collectDeposit-title">Record New Deposit Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
 <div class="modal-body"><p class="small text-muted">Record a new receipt only. This also reduces the selected invoice balance.</p><form method="POST" action="{{ route('admin.booking.deposit.collect',$booking) }}" enctype="multipart/form-data">@csrf
<input type="hidden" name="submission_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
<label>Unpaid invoice</label><select name="invoice_id" class="form-select mb-2" required><option value="">Select invoice</option>@foreach($depositInvoices as $invoice)@if($invoice->balance_due>0)<option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} · Balance {{ $money($invoice->balance_due) }}</option>@endif @endforeach</select>
<div class="row g-2"><div class="col-6"><label>Amount (AED)</label><input type="number" name="amount" min="0.01" step="0.01" class="form-control" required></div><div class="col-6"><label>Date</label><input type="date" name="payment_date" value="{{ today()->toDateString() }}" class="form-control" required></div><div class="col-6"><label>Received Into</label><select name="bank_account_id" class="form-select" required><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></div><div class="col-6"><label>Method</label><select name="payment_method" class="form-select"><option>Bank Transfer</option><option>Cash</option><option>Card</option><option>Cheque</option></select></div><div class="col-6"><label>Reference</label><input name="reference" class="form-control" required></div><div class="col-6"><label>Receipt</label><input type="file" name="receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div></div><button class="btn btn-success mt-3">Record Actual Deposit Received</button></form></div></div></div></div>@if($canRefund)
<div class="modal fade deposit-modal" id="requestRefund" tabindex="-1" aria-labelledby="requestRefund-title" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title" id="requestRefund-title">Request Refund</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
 <div class="modal-body"><form method="POST" action="{{ route('admin.booking.deposit.request',$booking) }}" enctype="multipart/form-data">@csrf
<label>Inspection (optional)</label><select name="inspection_id" class="form-select mb-2"><option value="">No inspection linked</option>@foreach($booking->inspections as $inspection)<option value="{{ $inspection->id }}">{{ $inspection->inspection_number }} · {{ $inspection->type_label }}</option>@endforeach</select><label>Reason / Notes</label><textarea name="reason" class="form-control mb-3" minlength="5" required></textarea><fieldset class="mb-3"><legend class="fs-6">Refund Type</legend><label class="me-4"><input type="radio" name="refund_ui_type" value="full" checked> Full Refund</label><label><input type="radio" name="refund_ui_type" value="deductions"> With Deductions</label></fieldset>
<div id="deductionSection" hidden><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Item / Reason</th><th>Amount (AED)</th><th>Evidence</th><th></th></tr></thead><tbody id="deductionRows"></tbody></table></div><button type="button" id="addDeduction" class="btn btn-sm btn-outline-primary mb-2">+ Add Deduction</button></div><div class="alert alert-light border">Held {{ $money($totals['held']) }} · Proposed deductions <strong id="deductionTotal">AED 0.00</strong> · Proposed refund <strong id="refundPreview">{{ $money($totals['held']) }}</strong></div><p class="small text-muted">Deductions require evidence. Approved deductions remain in a holding account pending final account allocation. Approval does not transfer money.</p><button class="btn btn-primary">Submit for Admin Approval</button></form></div></div></div></div>
@endif
@if($canCarry)
<div class="modal fade deposit-modal" id="carryDeposit" tabindex="-1" aria-labelledby="carryDeposit-title" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title" id="carryDeposit-title">Carry Forward to Renewal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
 <div class="modal-body"><div class="alert alert-info">Same guest and unit only. Set the renewal’s Security Deposit to 0 first. No new collection or cash movement.</div><form method="POST" action="{{ route('admin.booking.deposit.carry',$booking) }}">@csrf<input type="hidden" name="submission_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><label>Linked renewal</label><select name="target_id" class="form-select mb-2" required>@foreach($booking->renewals as $renewal)<option value="{{ $renewal->id }}">{{ $renewal->booking_reference }}</option>@endforeach</select><label>Amount (AED)</label><input type="number" name="amount" min="0.01" step="0.01" max="{{ $totals['held'] }}" value="{{ $totals['held'] }}" class="form-control mb-2" required><button class="btn btn-outline-primary">Carry Forward</button></form></div></div></div></div>
@endif
@foreach($refunds as $refund)
<div class="modal fade deposit-modal" id="refund-{{ $refund->id }}" tabindex="-1" aria-labelledby="refund-{{ $refund->id }}-title" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title" id="refund-{{ $refund->id }}-title">{{ $refund->request_no }} · {{ ucfirst($refund->status) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
 <div class="modal-body"><p class="small text-muted">Requested by {{ $refund->requester?->name }} · {{ $refund->created_at?->format('d M Y H:i') }} @if($refund->reviewed_at) · Reviewed by {{ $refund->reviewer?->name }} · {{ $refund->reviewed_at->format('d M Y H:i') }} @endif</p><p>{{ $refund->reason }}</p><dl class="deposit-breakdown">
<dt>Guest</dt><dd>{{ $booking->guest_name }}</dd>
<dt>Unit</dt><dd>{{ $booking->property?->building?->name }} — {{ $booking->property?->name }}</dd>
<dt>Deposit Received / Held at Request</dt><dd>{{ $money($refund->held_at_request) }}</dd>
<dt>Proposed Deduction</dt><dd class="text-danger">{{ $money($refund->deduction_amount) }}</dd>
<dt>Proposed Refundable</dt><dd class="text-success">{{ $money($refund->refund_amount) }}</dd>
<dt>Actually Paid</dt><dd>{{ $money($refund->paid_amount) }}</dd>
</dl>
@if($refund->inspection)<a href="{{ route('admin.inspection.show',$refund->inspection) }}">View Inspection</a>@endif
@foreach($refund->deductions??[] as $deduction)<p class="small mb-1">{{ $deduction['description'] }} — {{ $money($deduction['amount']) }} <a href="{{ \App\Support\MediaStorage::url($deduction['evidence']??null) }}" target="_blank" rel="noopener">Evidence</a></p>@endforeach
@if($refund->review_notes)<p>Review notes: {{ $refund->review_notes }}</p>@endif
@if($refund->status==='pending')<hr><form method="POST" action="{{ route('admin.booking.deposit.review',[$booking,$refund]) }}">@csrf<label>Approval Notes</label><textarea name="review_notes" class="form-control mb-2" minlength="3" required></textarea><button name="decision" value="rejected" class="btn btn-outline-danger">Reject</button> <button name="decision" value="approved" class="btn btn-primary">Approve Refund — No Cash Movement</button></form>@endif</div></div></div></div>
@if($refund->status==='approved')
<div class="modal fade deposit-modal" id="pay-{{ $refund->id }}" tabindex="-1" aria-labelledby="pay-{{ $refund->id }}-title" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title" id="pay-{{ $refund->id }}-title">Record Actual Refund</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
 <div class="modal-body"><div class="alert alert-info">{{ $refund->request_no }} · Remaining {{ $money($refund->remaining_amount) }}</div><form class="mt-3" method="POST" action="{{ route('admin.booking.deposit.pay',[$booking,$refund]) }}" enctype="multipart/form-data">@csrf<input type="hidden" name="submission_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><div class="row g-2"><div class="col-md-4"><label>Amount (AED)</label><input type="number" name="amount" step="0.01" min="0.01" max="{{ $refund->remaining_amount }}" value="{{ $refund->remaining_amount }}" class="form-control" required></div><div class="col-md-4"><label>Refund Date</label><input type="date" name="entry_date" min="{{ $refund->reviewed_at?->toDateString() }}" max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" class="form-control" required></div><div class="col-md-4"><label>Pay From</label><select name="bank_account_id" class="form-select" required><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></div><div class="col-md-4"><label>Recipient</label><input name="recipient" value="{{ $booking->guest_name }}" class="form-control" required></div><div class="col-md-4"><label>Method</label><select name="payment_method" class="form-select"><option>Bank Transfer</option><option>Cash</option><option>Card</option><option>Cheque</option></select></div><div class="col-md-4"><label>Transfer / Receipt Reference</label><input name="reference" class="form-control" required></div><div class="col-md-6"><label>Proof of Actual Payment</label><input type="file" name="proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required></div><div class="col-md-6"><label>Notes</label><input name="notes" class="form-control"></div></div><div class="deposit-notice mt-3 d-flex justify-content-between"><span>Deposit balance after payment</span><strong class="text-success" data-refund-balance data-held="{{ $totals['held'] }}">{{ $money(max(0,$totals['held']-$refund->remaining_amount)) }}</strong></div><p class="small text-muted mt-2">Partial refunds supported. This records a payment already made; it does not send a bank transfer.</p><button class="btn btn-primary">Confirm Refund Paid</button></form></div></div></div></div>
@endif
@endforeach
@foreach($entries as $entry)
<div class="modal fade deposit-modal" id="entry-{{ $entry->id }}" tabindex="-1" aria-labelledby="entry-{{ $entry->id }}-title" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
 <div class="modal-header"><h5 class="modal-title" id="entry-{{ $entry->id }}-title">Deposit Transaction</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
 <div class="modal-body">
<dl class="row">
<dt class="col-sm-4">Activity</dt><dd class="col-sm-8">{{ ucfirst(str_replace('_',' ',$entry->kind)) }}</dd>
<dt class="col-sm-4">Amount</dt><dd class="col-sm-8">{{ $money($entry->amount) }}</dd>
<dt class="col-sm-4">Date</dt><dd class="col-sm-8">{{ $entry->entry_date?->format('d M Y') }}</dd>
<dt class="col-sm-4">Account</dt><dd class="col-sm-8">{{ $entry->bankAccount?->name ?? 'No cash movement' }}</dd>
<dt class="col-sm-4">Reference / Method</dt><dd class="col-sm-8">{{ $entry->reference ?? '—' }} · {{ $entry->payment_method ?? '—' }}</dd>
<dt class="col-sm-4">Recipient</dt><dd class="col-sm-8">{{ $entry->recipient ?? '—' }}</dd>
<dt class="col-sm-4">Recorded by</dt><dd class="col-sm-8">{{ $entry->creator?->name ?? 'System' }}</dd>
<dt class="col-sm-4">Notes</dt><dd class="col-sm-8 text-break">{{ $entry->notes ?? '—' }}</dd>
</dl>
@if($entry->relatedBooking)<p>Related booking: <a href="{{ route('admin.booking.deposit-wallet',$entry->relatedBooking) }}">{{ $entry->relatedBooking->booking_reference }}</a></p>@endif
@if($entry->receipt_path)<a class="btn btn-outline-primary" href="{{ \App\Support\MediaStorage::url($entry->receipt_path) }}" target="_blank" rel="noopener">View Payment Proof</a>@endif
@if($entry->kind==='refunded')<a class="btn btn-primary" href="{{ route('admin.booking.deposit.receipt',[$booking,$entry]) }}">Refund Receipt PDF</a>@endif
</div></div></div></div>
@endforeach
</div>
@endsection
@push('styles')
<style>
.deposit-wallet .card{border:1px solid var(--bs-border-color,#e7eaf0);border-radius:12px;overflow:hidden;box-shadow:0 3px 12px rgba(25,40,70,.03)}
.deposit-wallet .card-header{padding:20px;background:transparent;border-bottom:1px solid var(--bs-border-color,#e7eaf0)}
.deposit-tabs{border-bottom:1px solid #e6e5ed}.deposit-tabs .nav-link{color:inherit;padding:12px 18px}.deposit-tabs .active{color:#5131dc;border-bottom:2px solid #6345ed}
.deposit-wallet .btn-primary,.deposit-modal .btn-primary{background:linear-gradient(110deg,#603bec,#4322c8);border-color:#5733d9}
.deposit-metrics{display:flex;border:1px solid var(--bs-border-color,#e7e7ef);border-radius:6px;background:var(--bs-tertiary-bg,#fafafd)}
.deposit-metrics>div{flex:1;padding:14px 8px;text-align:center;border-inline-end:1px solid var(--bs-border-color,#e7e7ef);min-width:0}.deposit-metrics>div:last-child{border:0}.deposit-metrics small{display:block;font-size:11px;margin-bottom:9px}.deposit-metrics strong{font-size:13px}
.deposit-notice{padding:12px;border:1px solid #d9def4;background:var(--bs-tertiary-bg,#f7f8ff);border-radius:6px;font-size:12px}
.deposit-breakdown{display:grid;grid-template-columns:1fr 1fr;gap:12px;border-bottom:1px solid #e5e5ef;padding-bottom:18px}.deposit-breakdown dt{font-weight:400}.deposit-breakdown dd{text-align:end;margin:0}
.deposit-timeline{list-style:none;margin:0 0 20px 10px;padding:0 0 0 24px;border-left:2px solid #e5e3ef}.deposit-timeline li{position:relative;padding:0 0 26px}.deposit-timeline li:before{content:'';position:absolute;left:-31px;top:3px;border:3px solid #fff;width:12px;height:12px;border-radius:50%;background:#5935d9;box-shadow:0 0 0 1px #5935d9}
.deposit-modal .modal-dialog{max-width:640px}.deposit-modal .modal-title{font-size:16px}.deposit-modal .modal-content{box-shadow:0 16px 60px #15113630}
@media(max-width:575px){.deposit-metrics{flex-wrap:wrap}.deposit-metrics>div{flex-basis:45%}.deposit-breakdown{font-size:12px}}
.deposit-overview .card-body{padding:28px}
.deposit-overview h2{color:#fff;font-size:2rem;font-variant-numeric:tabular-nums}
.deposit-muted{color:#bed0e3}
.deposit-pill{display:inline-block;background:#ffffff16;padding:6px 10px;border-radius:6px;font-size:12px}
.deposit-wallet th{font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--bs-secondary-color,#6d7a8b);background:var(--bs-tertiary-bg,#f8f9fc)}
.deposit-wallet td,.deposit-wallet th{padding:16px 20px}
.deposit-wallet td{font-size:13px}
.deposit-search{max-width:250px}
.deposit-modal .modal-content{border-radius:14px}
.deposit-modal .modal-header,.deposit-modal .modal-body{padding:24px}
.deposit-modal label{font-size:13px;font-weight:500;margin-bottom:6px}
.deposit-modal form>.btn{margin-top:12px}
.deposit-modal dd{overflow-wrap:anywhere}
@media(max-width:575px){.deposit-overview .card-body{padding:20px}.deposit-search{max-width:100%}.deposit-modal .modal-body{padding:16px}}
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
 const status=document.getElementById('refundStatus');
 status.addEventListener('change',()=>{let visible=0;document.querySelectorAll('#refundTable [data-status]').forEach(row=>{row.hidden=!!status.value&&row.dataset.status!==status.value;if(!row.hidden)visible++;});document.getElementById('refundFilterEmpty').hidden=visible>0||!status.value;});
 const search=document.getElementById('depositSearch');
 search.addEventListener('input',()=>{let visible=0;const term=search.value.trim().toLowerCase();document.querySelectorAll('#depositTable [data-entry]').forEach(row=>{row.hidden=!row.textContent.toLowerCase().includes(term);if(!row.hidden)visible++;});document.getElementById('depositFilterEmpty').hidden=visible>0||!term;});
 document.querySelectorAll('[data-refund-balance]').forEach(balance=>{const input=balance.closest('form').querySelector('[name="amount"]');input.addEventListener('input',()=>{balance.textContent='AED '+(Number(balance.dataset.held)-Number(input.value||0)).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});});});
 const rows=document.getElementById('deductionRows'),add=document.getElementById('addDeduction');if(!rows||!add)return;let next=0;
 const calculate=()=>{let total=0;rows.querySelectorAll('[data-amount]').forEach(i=>total+=Number(i.value||0));const fmt=n=>'AED '+n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});document.getElementById('deductionTotal').textContent=fmt(total);document.getElementById('refundPreview').textContent=fmt(@json((float)$totals['held'])-total);};
 add.addEventListener('click',()=>{if(rows.children.length>=20)return;const n=next++,row=document.createElement('tr');row.className='align-middle';row.innerHTML=`<td><input name="deductions[${n}][description]" class="form-control" aria-label="Item or reason" required></td><td><input name="deductions[${n}][amount]" type="number" min="0.01" step="0.01" class="form-control" aria-label="Deduction amount" data-amount required></td><td><input name="deductions[${n}][evidence]" type="file" accept=".pdf,.jpg,.jpeg,.png" class="form-control" aria-label="Deduction evidence" required></td><td><button type="button" class="btn btn-outline-danger" aria-label="Remove deduction">×</button></td>`;row.querySelector('button').addEventListener('click',()=>{row.remove();calculate();});rows.appendChild(row);});rows.addEventListener('input',calculate);
 document.querySelectorAll('[name="refund_ui_type"]').forEach(radio=>radio.addEventListener('change',()=>{const full=radio.value==='full';document.getElementById('deductionSection').hidden=full;if(full){rows.replaceChildren();calculate();}else if(!rows.children.length){add.click();}}));
});
</script>
@endpush
