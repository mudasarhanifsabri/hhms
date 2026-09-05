@extends('layouts.app')
@section('hide-page-title', 'true')

@push('styles')
<style>
.invoice-hover-preview{display:none;position:fixed;z-index:1080;width:275px;padding:16px;border:1px solid var(--bs-border-color,#e5e5ef);border-radius:8px;background:var(--bs-body-bg,#fff);box-shadow:0 8px 25px #18133b25;font-size:12px;pointer-events:none}
.invoice-hover-preview div{display:flex;justify-content:space-between;gap:15px;margin-bottom:5px}
.invoice-preview-wrap:hover .invoice-hover-preview,.invoice-preview-wrap:focus-within .invoice-hover-preview{display:block}
.booking-page-head{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:18px}.booking-page-head h3{font-size:24px;letter-spacing:-.02em;margin:0 0 4px}.booking-page-head .breadcrumb-note{color:#7a8496;font-size:13px}.booking-head-actions{display:flex;flex-wrap:wrap;gap:8px}
.booking-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:18px}.booking-summary-card{background:#fff;border:1px solid #e5e9f1;border-radius:12px;padding:16px;box-shadow:0 3px 12px #17244008;display:flex;align-items:center;gap:13px}.booking-summary-icon{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#f0edff;color:#6341df;font-size:22px;flex:0 0 auto}.booking-summary-card.success .booking-summary-icon{background:#eafaf7;color:#0aa581}.booking-summary-card.warning .booking-summary-icon{background:#fff3e8;color:#f1782f}.booking-summary-card.info .booking-summary-icon{background:#edf5ff;color:#3978dc}.booking-summary-label{font-size:12px;color:#748096;margin-bottom:2px}.booking-summary-value{font-size:19px;font-weight:750;line-height:1.2;color:#19233a}.booking-summary-card.success .booking-summary-value{color:#07966f}.booking-summary-card.warning .booking-summary-value{color:#e75526}.booking-summary-detail{font-size:12px;color:#657086;margin-top:3px}
.guest-card .card-header{border-bottom:0;padding-bottom:0}.guest-avatar{width:52px;height:52px;border-radius:50%;display:grid;place-items:center;background:#eeeaff;color:#6045d9;font-size:19px;font-weight:700}.guest-name{font-size:16px;font-weight:700;color:#182238}.booking-detail-label{display:block;color:#7a8496;font-size:11px;text-transform:uppercase;letter-spacing:.035em;margin-bottom:3px}.booking-detail-value{font-size:13px;color:#24324b}.booking-side-title{font-size:15px;font-weight:700}.booking-account-note{background:#f2f7ff;border-color:#cfe0fb!important}.booking-account-note strong{color:#1552a1}.booking-workspace .invoice-table thead th{white-space:nowrap;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.025em;border-bottom-width:1px}.booking-workspace .invoice-table tfoot td{font-weight:700;background:#fafbfc}.booking-workspace .invoice-number{font-weight:500;text-decoration:none}.booking-workspace .invoice-actions{white-space:nowrap}.booking-workspace .invoice-actions .btn{padding:6px 10px}.booking-side .card{border-radius:12px}.booking-side .alert{border-radius:9px}.status-explanation{font-size:12px;color:#748096}.document-actions .btn{flex:1 1 auto}.owner-posting-card{border:1px solid #cee0ff;border-radius:11px;background:#f5f9ff;padding:15px 18px;margin-bottom:16px;color:#32547f}
@media(max-width:1199px){.booking-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:767px){.booking-page-head{display:block}.booking-head-actions{margin-top:12px}.booking-summary{grid-template-columns:1fr}.booking-page-head h3{font-size:20px}}
</style>
@endpush

@section('content')
@include('admin.bookings.partials.compact-style')
<div class="booking-workspace">
@php
    $contractDays = $booking->nights;
    $remainingContractDays = max(0, 90 - $contractDays);
    $contractLimitDate = $booking->check_in?->copy()->addDays(90);
    $latestInvoice = $booking->invoices->sortByDesc('created_at')->first();
    $defaultExtensionRent = (float) ($latestInvoice?->rent_amount ?? $booking->rent_amount);
    $totalInvoiced = (float) $booking->invoices->sum('total_amount');
    $totalPaid = (float) $booking->invoices->sum(fn ($invoice) => $invoice->paid_amount);
    $totalOutstanding = max(0, $totalInvoiced - $totalPaid);
    $expectedRentOutstanding = max(0, $booking->invoices->sum('rent_amount') - $booking->invoices->sum(fn($invoice) => $invoice->payments->sum('rent_amount')));
    $outstandingInvoices = $booking->invoices->filter(fn ($invoice) => $invoice->balance_due > 0)->sortBy('issue_date');
@endphp
<div class="booking-page-head" role="region" aria-label="Booking details and actions">
    <div><h3>{{ $booking->booking_reference }}</h3><div class="breadcrumb-note"><a href="{{ route('admin.booking.index') }}">Bookings</a> / Booking details</div></div>
    <div class="booking-head-actions">
        <div class="dropdown"><button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"><iconify-icon icon="solar:documents-broken" class="align-middle fs-18"></iconify-icon> Documents</button><div class="dropdown-menu dropdown-menu-end"><a href="{{ route('admin.booking.invoice', $booking) }}" class="dropdown-item">Original booking invoice</a><a href="{{ route('admin.booking.confirmation', $booking) }}" class="dropdown-item">Overall booking confirmation</a><a href="{{ route('admin.booking.history', $booking) }}" class="dropdown-item">History & corrections</a></div></div>
        <a href="{{ route('admin.booking.edit', $booking) }}" class="btn btn-outline-dark"><iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon> Edit booking</a>
        @if($latestInvoice && $latestInvoice->balance_due > 0)<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $latestInvoice->id }}"><iconify-icon icon="solar:card-transfer-broken" class="align-middle fs-18"></iconify-icon> Record payment</button>@endif
        @if($outstandingInvoices->count() > 1)<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#combinedPaymentModal"><iconify-icon icon="solar:layers-minimalistic-broken" class="align-middle fs-18"></iconify-icon> Combined payment</button>@endif
        <div class="dropdown"><button class="btn btn-light" data-bs-toggle="dropdown" aria-label="More booking actions"><iconify-icon icon="solar:menu-dots-bold"></iconify-icon></button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="{{ route('admin.booking.history', $booking) }}">View history</a><div class="dropdown-divider"></div><form action="{{ route('admin.booking.destroy', $booking) }}" method="POST" onsubmit="return confirm('Delete this booking? Bookings with financial or deposit history cannot be deleted.');">@csrf @method('DELETE')<button class="dropdown-item text-danger">Delete booking</button></form></div></div>
    </div>
</div>
<section class="booking-summary" aria-label="Booking financial summary">
    <div class="booking-summary-card"><div class="booking-summary-icon"><iconify-icon icon="solar:bill-list-broken"></iconify-icon></div><div><div class="booking-summary-label">Total invoiced</div><div class="booking-summary-value">AED {{ number_format($totalInvoiced, 2) }}</div></div></div>
    <div class="booking-summary-card success"><div class="booking-summary-icon"><iconify-icon icon="solar:wallet-money-broken"></iconify-icon></div><div><div class="booking-summary-label">Payments received</div><div class="booking-summary-value">AED {{ number_format($totalPaid, 2) }}</div></div></div>
    <div class="booking-summary-card warning"><div class="booking-summary-icon"><iconify-icon icon="solar:danger-circle-broken"></iconify-icon></div><div><div class="booking-summary-label">Outstanding balance</div><div class="booking-summary-value">AED {{ number_format($totalOutstanding, 2) }}</div></div></div>
    <div class="booking-summary-card info"><div class="booking-summary-icon"><iconify-icon icon="solar:calendar-date-broken"></iconify-icon></div><div><div class="booking-summary-label">Contract duration</div><div class="booking-summary-value">{{ $contractDays }} days</div><div class="booking-summary-detail">{{ $booking->check_in?->format('d M') }} – {{ $booking->check_out?->format('d M Y') }}</div></div></div>
</section>
<div class="row">
    <div class="col-12">@include('admin.bookings.partials.navigation')</div>
    <div class="col-xl-8">
        <div class="card guest-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Guest & Stay</h4>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge {{ $booking->workflow_status_class }} text-white">{{ $booking->workflow_status_label }}</span>
                </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success m-3 mb-0">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger m-3 mb-0">{{ $errors->first() }}</div>
            @endif
            <div class="card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6"><div class="d-flex gap-3 align-items-center"><div class="guest-avatar">{{ collect(explode(' ', $booking->guest_name))->filter()->take(2)->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') }}</div><div><div class="guest-name">{{ $booking->guest_name }}</div><div class="booking-detail-value mt-1">{{ $booking->property?->building?->building_name ? $booking->property->building->building_name.' — ' : '' }}{{ $booking->property?->name ?? 'N/A' }}</div><div class="mt-1">@if($booking->guest_passport_id_no)<span class="booking-detail-value">ID {{ $booking->guest_passport_id_no }}</span>@else<span class="badge bg-warning-subtle text-warning">Passport / ID missing</span>@endif</div></div></div></div>
                    <div class="col-lg-3"><span class="booking-detail-label">Check-in</span><span class="booking-detail-value">{{ $booking->check_in?->format('d M Y') }} · {{ $booking->check_in_time ? \Carbon\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00' }}</span></div>
                    <div class="col-lg-3"><span class="booking-detail-label">Check-out</span><span class="booking-detail-value">{{ $booking->check_out?->format('d M Y') }} · {{ $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00' }}</span></div>
                    <div class="col-lg-4"><span class="booking-detail-label">Email</span><span class="booking-detail-value">{{ $booking->guest_email }}</span></div>
                    <div class="col-lg-4"><span class="booking-detail-label">Phone</span><span class="booking-detail-value">{{ $booking->guest_phone }}</span></div>
                    <div class="col-lg-4"><span class="booking-detail-label">Agent</span><span class="booking-detail-value">{{ $booking->agent?->name ?? 'Not assigned' }}</span></div>
                    @if($booking->guest_document)
                        <div class="col-lg-12"><a href="{{ asset($booking->guest_document) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Guest Attachment</a></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0" id="bookingInvoices">Invoices, Extensions & Renewals</h4>
                <span class="badge bg-light-subtle text-muted border">{{ $booking->invoices->count() }} records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 invoice-table">
                        <thead><tr><th>Type / Invoice</th><th>Period</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        @forelse($booking->invoices->sortBy('created_at') as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->type_label }}</strong><div class="invoice-preview-wrap"><button type="button" class="btn btn-link btn-sm p-0 invoice-number" data-bs-toggle="modal" data-bs-target="#invoiceDetails{{ $invoice->id }}" aria-describedby="invoicePreview{{ $invoice->id }}">{{ $invoice->invoice_number }}</button>
                                    <div class="invoice-hover-preview" role="tooltip" id="invoicePreview{{ $invoice->id }}">
                                        <div><span>Rent</span><strong>AED {{ number_format((float)$invoice->rent_amount,2) }}</strong></div>
                                        <div><span>VAT</span><strong>AED {{ number_format((float)$invoice->vat_amount,2) }}</strong></div>
                                        <div><span>Other fees</span><strong>AED {{ number_format(collect($invoice->fees ?? [])->except('Security Deposit')->sum(),2) }}</strong></div>
                                        <div><span>Deposit</span><strong>AED {{ number_format((float)(($invoice->fees ?? [])['Security Deposit'] ?? 0),2) }}</strong></div>
                                        <div class="border-top pt-2 mt-2"><span>Total</span><strong>AED {{ number_format((float)$invoice->total_amount,2) }}</strong></div>
                                    </div></div></td>
                                <td>{{ $invoice->period_from?->format('d M Y') }}<br><span class="small text-muted">to {{ $invoice->period_to?->format('d M Y') }}</span></td>
                                <td>AED {{ number_format((float) $invoice->total_amount, 2) }}</td>
                                <td class="text-success">AED {{ number_format($invoice->paid_amount, 2) }}</td>
                                <td class="{{ $invoice->balance_due > 0 ? 'text-danger' : 'text-success' }}">AED {{ number_format($invoice->balance_due, 2) }}</td>
                                <td><span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : ($invoice->status === 'partial' ? 'bg-warning' : 'bg-danger') }}">{{ ucfirst($invoice->status) }}</span></td>
                                <td><div class="d-flex gap-1 invoice-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#invoiceDetails{{ $invoice->id }}">View</button>
                                    @if($invoice->balance_due > 0)
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $invoice->id }}">Pay</button>
                                    @endif
                                    <div class="dropdown"><button class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-label="More invoice actions"><iconify-icon icon="solar:menu-dots-bold"></iconify-icon></button><div class="dropdown-menu dropdown-menu-end">
                                        @if(\Illuminate\Support\Facades\Route::has('admin.booking-invoice.confirmation'))
                                        @if($invoice->status === 'paid' && (float) $invoice->payments->sum('amount') >= (float) $invoice->total_amount)
                                        <a class="dropdown-item" href="{{ route('admin.booking-invoice.confirmation', $invoice) }}"><iconify-icon icon="solar:document-add-broken" class="me-2"></iconify-icon>Period confirmation PDF</a>
                                        @else<span class="dropdown-item-text small text-muted">Confirmation locked until fully paid</span>@endif
                                        @else
                                        <span class="dropdown-item-text small text-warning">Period confirmation unavailable: refresh server route cache.</span>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('admin.accounting.booking-invoices.pdf', $invoice) }}"><iconify-icon icon="solar:bill-list-broken" class="me-2"></iconify-icon>Invoice PDF</a>
                                        @if($invoice->payments->isNotEmpty())<a class="dropdown-item" href="{{ route('admin.booking-invoice.receipt', $invoice) }}"><iconify-icon icon="solar:bill-check-broken" class="me-2"></iconify-icon>Payment receipt</a>@endif
                                        @if($invoice->status==='unpaid' && $invoice->payments->isEmpty())<button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#correctInvoice{{ $invoice->id }}"><iconify-icon icon="solar:pen-2-broken" class="me-2"></iconify-icon>Edit invoice</button>@endif
                                    </div></div>
                                </div></td>
                            </tr>
                            @if($invoice->payments->isNotEmpty())
                                <tr class="table-light"><td colspan="7" class="small">
                                    <strong>Payments:</strong>
                                    @foreach($invoice->payments as $payment)
                                        <span class="ms-2">{{ $payment->payment_date?->format('d M Y') }} · AED {{ number_format((float) $payment->amount, 2) }} · {{ $payment->payment_method }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</span>
                                    @endforeach
                                </td></tr>
                            @endif
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No invoices created.</td></tr>
                        @endforelse
                        </tbody>
                        @if($booking->invoices->isNotEmpty())<tfoot><tr><td colspan="2">Total</td><td>AED {{ number_format($totalInvoiced, 2) }}</td><td class="text-success">AED {{ number_format($totalPaid, 2) }}</td><td class="text-danger">AED {{ number_format($totalOutstanding, 2) }}</td><td colspan="2"></td></tr></tfoot>@endif
                    </table>
                </div>
                <div class="px-3 py-2 border-top small text-muted"><iconify-icon icon="solar:info-circle-broken" class="align-middle"></iconify-icon> Each invoice has its own confirmation PDF for that invoice’s exact period. Click an invoice number to view charges and documents.</div>
            </div>
        </div>

        @if($booking->agent_id)<div class="card"><div class="card-body"><h4 class="card-title">Agent commission · {{ $booking->agent?->name }}</h4><p class="small text-muted">{{ $booking->agent_commission_percent ?? $booking->agent?->agent_commission ?? 0 }}% of the agency fee. The company retains the remaining agency fee.</p>
        @if(!\App\Models\BookingInvoicePayment::whereHas('invoice', fn($q) => $q->where('booking_id', $booking->id))->exists())<form action="{{ route('admin.booking.agent-commission', $booking) }}" method="POST" class="row g-2">@csrf @method('PUT')<div class="col-md-3"><label class="form-label">Booking override %</label><input type="number" name="agent_commission_percent" class="form-control" min="0" max="100" step="0.01" value="{{ $booking->agent_commission_percent ?? $booking->agent?->agent_commission ?? 0 }}" required></div><div class="col-md-6"><label class="form-label">Reason</label><input name="reason" class="form-control" minlength="5" maxlength="500" required></div><div class="col-md-3 d-flex align-items-end"><button class="btn btn-outline-primary">Save rate</button></div></form>@else<p class="small text-muted mb-0">Commission locked because payment history exists.</p>@endif
        </div></div>@endif
        <div class="owner-posting-card"><div class="d-flex gap-3"><iconify-icon icon="solar:buildings-3-broken" class="fs-24 text-primary"></iconify-icon><div><strong>Owner posting</strong><div class="mt-1">Expected rent not collected: <strong>AED {{ number_format($expectedRentOutstanding, 2) }}</strong></div><small>Owner rent posts from received rent only. Security deposits are separate from owner income.</small>@if($booking->owner_posting_basis !== 'receipts')<div class="text-warning mt-1">Legacy owner posting requires reconciliation before changing its basis.</div>@endif</div></div></div>
    </div>

    <div class="col-xl-4 booking-side">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Stay Actions</h4></div>
            <div class="card-body">
                <div class="alert {{ $remainingContractDays > 0 ? 'alert-success' : 'alert-warning' }}">
                    <strong>{{ $contractDays }} of 90 contract days used.</strong><br>
                    @if($remainingContractDays > 0)
                        {{ $remainingContractDays }} days remain before renewal is required. Maximum checkout: {{ $contractLimitDate?->format('d M Y') }}.
                    @else
                        The 90-day limit is reached. Use Renew Contract; a new DTCM fee is required.
                    @endif
                </div>
                <div class="d-grid gap-2 mb-3">
                    @if($remainingContractDays > 0)<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#extendBookingModal"><iconify-icon icon="solar:calendar-add-broken" class="align-middle fs-18"></iconify-icon> Extend current period</button>@endif
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#renewBookingModal"><iconify-icon icon="solar:restart-circle-broken" class="align-middle fs-18"></iconify-icon> Renew contract</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge {{ $booking->workflow_status_class }} text-white">{{ $booking->workflow_status_label }}</span>
                    @if($booking->checked_in_at)
                        <span class="badge bg-light-subtle text-muted border">Checked in {{ $booking->checked_in_at->format('d M Y H:i') }}</span>
                    @endif
                    @if($booking->checked_out_at)
                        <span class="badge bg-light-subtle text-muted border">Checked out {{ $booking->checked_out_at->format('d M Y H:i') }}</span>
                    @endif
                </div>
                <div class="d-grid gap-2">
                    <form action="{{ route('admin.booking.check-in', $booking->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-outline-success w-100" {{ $booking->invoice_status !== 'paid' || $booking->checked_in_at ? 'disabled' : '' }}>
                            <iconify-icon icon="solar:clipboard-check-broken" class="align-middle fs-18"></iconify-icon>
                            Complete Check In
                        </button>
                    </form>
                    <div>
                        <button type="button" class="btn btn-outline-dark w-100" data-bs-toggle="modal" data-bs-target="#checkoutConfirmModal" {{ $booking->checked_out_at ? 'disabled' : '' }}>
                            <iconify-icon icon="solar:clipboard-remove-broken" class="align-middle fs-18"></iconify-icon>
                            Complete Check Out & Create Tasks
                        </button>
                    </div>
                    @if($booking->checked_out_at)
                    <form action="{{ route('admin.booking.reverse-checkout', $booking) }}" method="POST" onsubmit="return confirm('Restore this booking and cancel only untouched checkout tasks?');">
                        @csrf
                        <label class="form-label">Reason for reversing checkout</label>
                        <input name="reason" class="form-control mb-2" minlength="5" maxlength="1000" required placeholder="Checked out by mistake">
                        <button class="btn btn-outline-warning w-100">Reverse Accidental Checkout</button>
                    </form>
                    @endif
                </div>
                <div class="border-top mt-3 pt-3">
                    <p class="text-muted mb-2 small">Guest app inspection flow</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light-subtle text-muted border">Start Inspection</span>
                        <span class="badge bg-light-subtle text-muted border">Select Areas</span>
                        <span class="badge bg-light-subtle text-muted border">Inspect & Photos</span>
                        <span class="badge bg-light-subtle text-muted border">Review</span>
                        <span class="badge bg-light-subtle text-muted border">Notes</span>
                        <span class="badge bg-light-subtle text-muted border">Submit</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Security Deposit</h4></div>
            <div class="card-body"><div class="d-flex align-items-center gap-3 mb-3"><div class="booking-summary-icon"><iconify-icon icon="solar:shield-check-broken"></iconify-icon></div><div><strong>Company-held funds</strong><div class="text-muted small">Held balance: AED {{ number_format((float) ($depositTotals['held'] ?? 0), 2) }}</div></div></div><p class="small text-muted">Managed separately from owner rent and income.</p><a href="{{ route('admin.booking.deposit-wallet', $booking) }}" class="btn btn-outline-dark w-100">Manage deposit</a></div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Inspection Tracking</h4>
                <a href="{{ route('admin.inspection.index', ['q' => $booking->booking_reference]) }}" class="btn btn-sm btn-outline-light">All</a>
            </div>
            <div class="card-body">
                @forelse($booking->inspections as $inspection)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <strong>{{ $inspection->type_label }}</strong>
                                <p class="text-muted small mb-0">{{ $inspection->inspection_number }}</p>
                            </div>
                            <span class="badge {{ $inspection->status_class }} text-white">{{ $inspection->status_label }}</span>
                        </div>
                        <div class="small text-muted mt-2">Issues {{ $inspection->issue_items }} / {{ $inspection->total_items }}</div>
                        <div class="d-flex gap-2 mt-2">
                            <a href="{{ route('admin.inspection.show', $inspection->id) }}" class="btn btn-sm btn-light">Preview</a>
                            <a href="{{ route('admin.inspection.pdf', $inspection->id) }}" class="btn btn-sm btn-outline-primary">PDF</a>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No check-in or check-out inspection submitted yet.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="extendBookingModal" tabindex="-1" aria-labelledby="extendBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.booking.extend', $booking->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="extendBookingModalLabel">Extend Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="border rounded p-2"><span class="small text-muted">Previous checkout</span><br><strong>{{ $booking->check_out?->format('d M Y') }}</strong></div></div>
                        <div class="col-6"><div class="border rounded p-2"><span class="small text-muted">Maximum under this contract</span><br><strong>{{ $contractLimitDate?->format('d M Y') }}</strong></div></div>
                    </div>
                    <div class="mb-3">
                        <label for="extend_check_out" class="form-label">New Check Out Date</label>
                        <input type="date" id="extend_check_out" name="check_out" class="form-control" min="{{ $booking->check_out?->copy()->addDay()->format('Y-m-d') }}" max="{{ $contractLimitDate?->format('Y-m-d') }}" value="{{ old('check_out', $booking->check_out?->copy()->addDay()->min($contractLimitDate)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="extend_check_out_time" class="form-label">Check Out Time</label>
                        <input type="time" id="extend_check_out_time" name="check_out_time" class="form-control" value="{{ old('check_out_time', $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00') }}">
                    </div>
                    <div class="mb-3">
                        <label for="extension_rent_amount" class="form-label">New Extension Rent (AED)</label>
                        <input type="number" step="0.01" min="0" id="extension_rent_amount" name="extension_rent_amount" class="form-control extension-calculation" value="{{ old('extension_rent_amount', $defaultExtensionRent) }}" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6"><label class="form-label" for="extension_vat_rate">VAT Rate %</label><input class="form-control extension-calculation" type="number" step="0.01" min="0" max="100" id="extension_vat_rate" name="vat_rate" value="{{ old('vat_rate', 5) }}" required></div>
                        <div class="col-6"><label class="form-label" for="extension_other_fees">Other Fees (AED)</label><input class="form-control extension-calculation" type="number" step="0.01" min="0" id="extension_other_fees" name="other_fees" value="{{ old('other_fees', 0) }}"></div>
                    </div>
                    <div class="alert alert-primary d-flex justify-content-between"><span>Separate extension invoice total</span><strong id="extensionTotal">AED 0.00</strong></div>
                    <p class="small text-muted">The original booking invoice and its payments will not be changed.</p>
                    <div class="mb-0">
                        <label for="extension_notes" class="form-label">Notes</label>
                        <textarea id="extension_notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Extension Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="renewBookingModal" tabindex="-1" aria-labelledby="renewBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.booking.renew', $booking->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="renewBookingModalLabel">Renew Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-3">
                            <label for="renew_check_in" class="form-label">Check In</label>
                            <input type="date" id="renew_check_in" name="check_in" class="form-control" value="{{ old('check_in', $booking->check_out?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-lg-3">
                            <label for="renew_check_in_time" class="form-label">Check In Time</label>
                            <input type="time" id="renew_check_in_time" name="check_in_time" class="form-control" value="{{ old('check_in_time', $booking->check_in_time ? \Carbon\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00') }}">
                        </div>
                        <div class="col-lg-3">
                            <label for="renew_check_out" class="form-label">Check Out</label>
                            <input type="date" id="renew_check_out" name="check_out" class="form-control" value="{{ old('check_out', $booking->check_out?->copy()->addDays(90)->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-lg-3">
                            <label for="renew_check_out_time" class="form-label">Check Out Time</label>
                            <input type="time" id="renew_check_out_time" name="check_out_time" class="form-control" value="{{ old('check_out_time', $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00') }}">
                        </div>
                        <div class="col-lg-4">
                            <label for="renew_rent_amount" class="form-label">Rent</label>
                            <input type="number" step="0.01" min="0" id="renew_rent_amount" name="rent_amount" class="form-control" value="{{ old('rent_amount', $booking->rent_amount) }}" required>
                        </div>
                        <div class="col-lg-4">
                            <label for="renew_dtcm_fee" class="form-label">DTCM Fee</label>
                            <input type="number" step="0.01" min="0" id="renew_dtcm_fee" name="dtcm_fee" class="form-control" value="{{ old('dtcm_fee', $booking->dtcm_fee) }}">
                        </div>
                        <div class="col-lg-4">
                            <label for="renew_cleaning_fee" class="form-label">Cleaning Fee</label>
                            <input type="number" step="0.01" min="0" id="renew_cleaning_fee" name="cleaning_fee" class="form-control" value="{{ old('cleaning_fee', $booking->cleaning_fee) }}">
                        </div>
                        <div class="col-lg-4">
                            <label for="renew_agency_fee" class="form-label">Agency Fee</label>
                            <input type="number" step="0.01" min="0" id="renew_agency_fee" name="agency_fee" class="form-control" value="{{ old('agency_fee', $booking->agency_fee) }}">
                        </div>
                        <div class="col-lg-4">
                            <label for="renew_security_deposit" class="form-label">Security Deposit</label>
                            <input type="number" step="0.01" min="0" id="renew_security_deposit" name="security_deposit" class="form-control" value="{{ old('security_deposit', $booking->security_deposit) }}">
                            <small class="text-muted">Set to 0 if carrying an existing deposit forward from the Deposit Wallet.</small>
                        </div>
                        <div class="col-lg-12">
                            <label for="renew_notes" class="form-label">Notes</label>
                            <textarea id="renew_notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <span class="me-auto small text-muted">New contract, DTCM fee, invoice and payment record.</span>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Renewal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="contractLimitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-body text-center p-4">
            <div class="text-warning mb-3"><iconify-icon icon="solar:danger-triangle-bold" class="fs-48"></iconify-icon></div>
            <h4>This guest has reached the maximum 90-day contract period.</h4>
            <p class="text-muted">A new contract and DTCM fee are required to continue the stay.</p>
            <div class="border rounded p-3 mb-4 d-flex justify-content-between"><span>Current contract</span><strong>{{ $booking->check_in?->format('d M Y') }} — {{ $contractLimitDate?->format('d M Y') }}</strong></div>
            <button class="btn btn-light" data-bs-dismiss="modal">Back</button>
            <button class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#renewBookingModal">Renew Contract</button>
        </div>
    </div></div>
</div>

@foreach($booking->invoices as $invoice)
@php
    $allocationError = null;
    try { $automaticAllocation = \App\Support\InvoiceSettlement::allocation($invoice); }
    catch (\Illuminate\Validation\ValidationException $e) { $automaticAllocation = []; $allocationError = collect($e->errors())->flatten()->first(); }
    $commissionRate = $booking->agent_id ? (float) ($booking->agent_commission_percent ?? $booking->agent?->agent_commission ?? 0) : 0;
@endphp
@include('admin.bookings.partials.invoice-edit-modal')
<div class="modal fade" id="invoiceDetails{{ $invoice->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5>{{ $invoice->type_label }} — {{ $invoice->invoice_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><table class="table"><tbody>
        <tr><td>Rent</td><td class="text-end">AED {{ number_format((float)$invoice->rent_amount,2) }}</td></tr>
        <tr><td>VAT recorded ({{ $invoice->vat_rate }}%)</td><td class="text-end">AED {{ number_format((float)$invoice->vat_amount,2) }}</td></tr>
        @foreach($invoice->fees ?? [] as $label => $amount)
            @if($label !== 'Security Deposit')
                <tr><td>{{ $label }}</td><td class="text-end">AED {{ number_format((float)$amount,2) }}</td></tr>
            @endif
        @endforeach
        @php($refundableDeposit = (float)(($invoice->fees ?? [])['Security Deposit'] ?? 0))
        <tr><th>Charges subtotal</th><th class="text-end">AED {{ number_format((float)$invoice->total_amount-$refundableDeposit,2) }}</th></tr>
        <tr class="table-primary"><td>Refundable Security Deposit</td><td class="text-end">AED {{ number_format($refundableDeposit,2) }}</td></tr>
        <tr><th>Total payable</th><th class="text-end">AED {{ number_format((float)$invoice->total_amount,2) }}</th></tr>
        <tr><td>Paid</td><td class="text-end text-success">AED {{ number_format($invoice->paid_amount,2) }}</td></tr>
        <tr><td>Balance</td><td class="text-end">AED {{ number_format($invoice->balance_due,2) }}</td></tr>
    </tbody></table><p>Which document would you like?</p>
    <a class="btn btn-primary" href="{{ route('admin.accounting.booking-invoices.pdf', $invoice) }}">Invoice PDF</a>
    @if(\Illuminate\Support\Facades\Route::has('admin.booking-invoice.confirmation'))
    @if($invoice->status === 'paid' && (float) $invoice->payments->sum('amount') >= (float) $invoice->total_amount)
    <a class="btn btn-outline-primary" href="{{ route('admin.booking-invoice.confirmation', $invoice) }}">Period Confirmation</a>
    @else<p class="small text-warning mt-2">Confirmation locked until the full invoice payment is recorded.</p>@endif
    @else
    <p class="small text-warning mt-2">Period confirmation unavailable. Complete the software update and refresh the server route cache.</p>
    @endif
    @if($invoice->payments->isNotEmpty())<a class="btn btn-outline-success" href="{{ route('admin.booking-invoice.receipt', $invoice) }}">Receipt — Amount Paid</a>@else<p class="small text-muted mt-2">A receipt requires an itemised payment record.</p>@endif
    </div>
</div></div></div>
<div class="modal fade" id="paymentModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form action="{{ route('admin.booking-invoice.payment', $invoice->id) }}" method="POST" enctype="multipart/form-data">@csrf
            <div class="modal-header"><h5 class="modal-title">Record Payment — {{ $invoice->invoice_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><div class="border rounded p-3"><span class="text-muted small">Invoice Total</span><h5>AED {{ number_format((float) $invoice->total_amount, 2) }}</h5></div></div>
                    <div class="col-md-4"><div class="border rounded p-3"><span class="text-muted small">Already Paid</span><h5 class="text-success">AED {{ number_format($invoice->paid_amount, 2) }}</h5></div></div>
                    <div class="col-md-4"><div class="border rounded p-3"><span class="text-muted small">Balance Due</span><h5 class="text-danger">AED {{ number_format($invoice->balance_due, 2) }}</h5></div></div>
                    <div class="col-md-6"><label class="form-label">Payment Date</label><input type="date" name="payment_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                    <div class="col-md-6"><label class="form-label">Amount received (AED)</label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required><small class="text-muted">Enter the actual amount received. Partial payments remain outstanding and each receipt is kept separately.</small></div>
                    <div class="col-md-6"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select" required><option>Bank Transfer</option><option>Cash</option><option>Card</option><option>Cheque</option><option>Online Payment</option></select></div>
                    <div class="col-md-6"><label class="form-label">Received into account</label><select name="bank_account_id" class="form-select" required><option value="">Select bank / cash account</option>@foreach($bankAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-12"><div class="border rounded p-3"><strong>Automatic invoice allocation</strong><p class="small text-muted">No charge entry needed. Amounts come from this invoice.</p>
                    @if($allocationError)<div class="alert alert-warning">{{ $allocationError }}</div>@else
                    <table class="table table-sm mb-0"><tbody>@foreach(['rent'=>'Rent (before management fee)', 'vat'=>'VAT payable', 'cleaning'=>'Cleaning income', 'agency'=>'Agency fee', 'tourism'=>'Tourism fees payable', 'other'=>'Other fees', 'deposit'=>'Company-held refundable deposit'] as $key=>$label)<tr><td>{{ $label }}</td><td class="text-end">AED {{ number_format($automaticAllocation[$key], 2) }}</td></tr>@endforeach
                    <tr class="table-light"><td>Agent commission — {{ $commissionRate }}% of agency fee</td><td class="text-end">AED {{ number_format(round($automaticAllocation['agency'] * $commissionRate / 100, 2), 2) }}</td></tr><tr class="table-light"><td>Company agency income</td><td class="text-end">AED {{ number_format($automaticAllocation['agency'] - round($automaticAllocation['agency'] * $commissionRate / 100, 2), 2) }}</td></tr></tbody></table><small class="text-muted">Agent commission is part of the agency fee, not an additional guest charge. It is recorded as payable, not paid to the agent.</small>@endif
                    </div></div>
                    <div class="col-md-6"><label class="form-label">Bank transaction reference</label><input name="reference" class="form-control" placeholder="Reference shown on the bank statement"><small class="text-muted">Keep this reference for comparison with your bank statement.</small></div>
                    <div class="col-md-6"><label class="form-label">Upload Receipt</label><input type="file" name="receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary" @disabled($allocationError)>Record Payment</button></div>
        </form>
    </div></div>
</div>
@endforeach

@if($outstandingInvoices->count() > 1)
<div class="modal fade" id="combinedPaymentModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><form class="modal-content" method="POST" enctype="multipart/form-data" action="{{ route('admin.booking.combined-payment', $booking) }}">@csrf
<input type="hidden" name="submission_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
<div class="modal-header"><div><h5 class="modal-title">One Transfer Across Invoices</h5><small class="text-muted">Oldest outstanding invoice is allocated first.</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><div class="alert alert-info">One bank transaction is recorded, with separate payment allocations under each invoice.</div>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Order</th><th>Invoice</th><th>Period</th><th class="text-end">Outstanding</th></tr></thead><tbody>@foreach($outstandingInvoices as $openInvoice)<tr><td>{{ $loop->iteration }}</td><td>{{ $openInvoice->invoice_number }}</td><td>{{ $openInvoice->period_from?->format('d M') }} – {{ $openInvoice->period_to?->format('d M Y') }}</td><td class="text-end">AED {{ number_format($openInvoice->balance_due,2) }}</td></tr>@endforeach<tr class="table-light fw-bold"><td colspan="3">Combined outstanding</td><td class="text-end">AED {{ number_format($outstandingInvoices->sum(fn($item) => $item->balance_due),2) }}</td></tr></tbody></table></div>
<div class="row g-3"><div class="col-md-6"><label class="form-label">Transfer date</label><input class="form-control" type="date" name="payment_date" value="{{ today()->toDateString() }}" required></div><div class="col-md-6"><label class="form-label">Amount received (AED)</label><input class="form-control" type="number" name="amount" min="0.01" max="{{ $outstandingInvoices->sum(fn($item) => $item->balance_due) }}" step="0.01" required></div>
<div class="col-md-6"><label class="form-label">Payment method</label><select class="form-select" name="payment_method" required><option>Bank Transfer</option><option>Cash</option><option>Card</option><option>Cheque</option><option>Online Payment</option></select></div><div class="col-md-6"><label class="form-label">Received into account</label><select class="form-select" name="bank_account_id" required><option value="">Select bank / cash account</option>@foreach($bankAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Bank transaction reference</label><input class="form-control" name="reference" required maxlength="150"></div><div class="col-md-6"><label class="form-label">Transfer proof</label><input class="form-control" type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png"></div><div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2"></textarea></div></div></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Record & Allocate Transfer</button></div></form></div></div>
@endif

<div class="modal fade" id="checkoutConfirmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="{{ route('admin.booking.check-out', $booking) }}">@csrf
        <input type="hidden" name="checkout_confirmation" id="checkoutToken">
        <div class="modal-header"><h5>Confirm Guest Checkout</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><strong>{{ $booking->guest_name }} — {{ $booking->booking_reference }}</strong><p>{{ $booking->property?->name }} · Scheduled checkout {{ $booking->check_out?->format('d M Y') }}</p><div class="alert alert-warning">This marks the guest checked out and creates checkout tasks. No action occurs until you confirm.</div><p id="checkoutWait" role="status">Preparing confirmation…</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" id="checkoutConfirmButton" disabled>Confirm Check Out</button></div>
    </form>
</div></div></div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkoutModal = document.getElementById('checkoutConfirmModal');
    let countdown, checkoutAttempt = 0;
    checkoutModal.addEventListener('show.bs.modal', async function () {
        const attempt = ++checkoutAttempt;
        const button = document.getElementById('checkoutConfirmButton');
        const status = document.getElementById('checkoutWait');
        button.disabled = true;
        document.getElementById('checkoutToken').value = '';
        status.textContent = 'Preparing confirmation…';
        try {
            const response = await fetch(@json(route('admin.booking.prepare-checkout', $booking)), {method: 'POST', headers: {'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Cannot prepare checkout');
            const data = await response.json();
            if (attempt !== checkoutAttempt) return;
            document.getElementById('checkoutToken').value = data.token;
            let seconds = 5;
            status.textContent = 'Please review. Confirm available in 5 seconds.';
            countdown = setInterval(function () {
                seconds--;
                status.textContent = seconds > 0 ? 'Confirm available in ' + seconds + ' seconds.' : 'Ready. Confirm only if the guest has left.';
                if (seconds <= 0) { clearInterval(countdown); button.disabled = false; }
            }, 1000);
        } catch (error) { status.textContent = 'Could not prepare checkout. Close and try again.'; }
    });
    checkoutModal.addEventListener('hidden.bs.modal', () => { checkoutAttempt++; clearInterval(countdown); document.getElementById('checkoutConfirmButton').disabled = true; });
    const rent = document.getElementById('extension_rent_amount');
    const vat = document.getElementById('extension_vat_rate');
    const fees = document.getElementById('extension_other_fees');
    const total = document.getElementById('extensionTotal');
    const calculate = () => total.textContent = 'AED ' + ((parseFloat(rent?.value) || 0) * (1 + (parseFloat(vat?.value) || 0) / 100) + (parseFloat(fees?.value) || 0)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.querySelectorAll('.extension-calculation').forEach(input => input.addEventListener('input', calculate));
    calculate();
});
</script>
@endpush
</div>
@endsection
