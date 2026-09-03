@extends('layouts.app')

@push('styles')
<style>
.invoice-hover-preview{display:none;position:fixed;z-index:1080;width:275px;padding:16px;border:1px solid var(--bs-border-color,#e5e5ef);border-radius:8px;background:var(--bs-body-bg,#fff);box-shadow:0 8px 25px #18133b25;font-size:12px;pointer-events:none}
.invoice-hover-preview div{display:flex;justify-content:space-between;gap:15px;margin-bottom:5px}
.invoice-preview-wrap:hover .invoice-hover-preview,.invoice-preview-wrap:focus-within .invoice-hover-preview{display:block}
</style>
@endpush

@section('content')
@php
    $contractDays = $booking->nights;
    $remainingContractDays = max(0, 90 - $contractDays);
    $contractLimitDate = $booking->check_in?->copy()->addDays(90);
    $latestInvoice = $booking->invoices->sortByDesc('created_at')->first();
    $defaultExtensionRent = (float) ($latestInvoice?->rent_amount ?? $booking->rent_amount);
@endphp
<div class="row">
    <div class="col-12 mb-3"><nav class="nav nav-underline gap-3"><a class="nav-link active" href="{{ route('admin.booking.show',$booking) }}">Overview</a><a class="nav-link" href="#bookingInvoices">Invoices</a><a class="nav-link" href="{{ route('admin.booking.deposit-wallet',$booking) }}">Security Deposit Wallet</a></nav></div>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">{{ $booking->booking_reference }}</h4>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge {{ $booking->workflow_status_class }} text-white">{{ $booking->workflow_status_label }}</span>
                    <a href="{{ route('admin.booking.edit', $booking->id) }}" class="btn btn-sm btn-soft-primary" title="Edit Booking" aria-label="Edit Booking">
                        <iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon>
                    </a>
                    <form action="{{ route('admin.booking.destroy', $booking->id) }}" method="POST" onsubmit="return confirm('Delete this booking?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-soft-danger" title="Delete Booking" aria-label="Delete Booking">
                            <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="align-middle fs-18"></iconify-icon>
                        </button>
                    </form>
                </div>
            </div>
            @if(session('success'))
                <div class="alert alert-success m-3 mb-0">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger m-3 mb-0">{{ $errors->first() }}</div>
            @endif
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6"><strong>Guest:</strong> {{ $booking->guest_name }}</div>
                    <div class="col-lg-6"><strong>Email:</strong> {{ $booking->guest_email }}</div>
                    <div class="col-lg-6"><strong>Phone:</strong> {{ $booking->guest_phone }}</div>
                    <div class="col-lg-6"><strong>Passport/ID:</strong> {{ $booking->guest_passport_id_no }}</div>
                    <div class="col-lg-6"><strong>Check In:</strong> {{ $booking->check_in?->format('d M Y') }}</div>
                    <div class="col-lg-6"><strong>Check Out:</strong> {{ $booking->check_out?->format('d M Y') }}</div>
                    <div class="col-lg-6"><strong>Check In Time:</strong> {{ $booking->check_in_time ? \Carbon\Carbon::parse($booking->check_in_time)->format('H:i') : '15:00' }}</div>
                    <div class="col-lg-6"><strong>Check Out Time:</strong> {{ $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00' }}</div>
                    <div class="col-lg-12"><strong>Unit:</strong> {{ $booking->property?->name ?? 'N/A' }}</div>
                    <div class="col-lg-12"><strong>Agent:</strong> {{ $booking->agent?->name ?? 'No Agent' }}</div>
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
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Type / Invoice</th><th>Period</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        @forelse($booking->invoices->sortBy('created_at') as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->type_label }}</strong><div class="invoice-preview-wrap"><button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#invoiceDetails{{ $invoice->id }}" aria-describedby="invoicePreview{{ $invoice->id }}">{{ $invoice->invoice_number }}</button>
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
                                <td><div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#invoiceDetails{{ $invoice->id }}">Documents</button>
                                    @if($invoice->balance_due > 0)
                                        <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#paymentModal{{ $invoice->id }}">Payment</button>
                                    @endif
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
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Charges</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr><td>Rent</td><td class="text-end">{{ number_format((float) $booking->rent_amount, 2) }} AED</td></tr>
                            <tr><td>VAT 5% {{ $booking->vat_included ? '(separated from rent)' : '' }}</td><td class="text-end">{{ number_format((float) $booking->vat_amount, 2) }} AED</td></tr>
                            <tr><td>DTCM Fee</td><td class="text-end">{{ number_format((float) $booking->dtcm_fee, 2) }} AED</td></tr>
                            <tr><td>Cleaning Fee</td><td class="text-end">{{ number_format((float) $booking->cleaning_fee, 2) }} AED</td></tr>
                            <tr><td>Agency Fee</td><td class="text-end">{{ number_format((float) $booking->agency_fee, 2) }} AED</td></tr>
                            <tr><td>Security Deposit</td><td class="text-end">{{ number_format((float) $booking->security_deposit, 2) }} AED</td></tr>
                            <tr class="fw-semibold"><td>Total</td><td class="text-end">{{ number_format((float) $booking->total_amount, 2) }} AED</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Documents</h4></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.booking.invoice', $booking->id) }}" class="btn btn-primary" title="Generate Invoice" aria-label="Generate Invoice">
                        <iconify-icon icon="solar:bill-list-broken" class="align-middle fs-18"></iconify-icon>
                    </a>
                    <a href="{{ route('admin.booking.confirmation', $booking->id) }}" class="btn btn-outline-primary" title="Booking Confirmation" aria-label="Booking Confirmation">
                        <iconify-icon icon="solar:document-add-broken" class="align-middle fs-18"></iconify-icon>
                    </a>
                    <a href="{{ route('admin.booking.history', $booking->id) }}" class="btn btn-light" title="History" aria-label="History">
                        <iconify-icon icon="solar:history-2-broken" class="align-middle fs-18"></iconify-icon>
                    </a>
                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="{{ $remainingContractDays > 0 ? '#extendBookingModal' : '#contractLimitModal' }}" title="Extend Booking" aria-label="Extend Booking">
                        <iconify-icon icon="solar:calendar-add-broken" class="align-middle fs-18"></iconify-icon>
                    </button>
                    <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#renewBookingModal" title="Renew Booking" aria-label="Renew Booking">
                        <iconify-icon icon="solar:restart-circle-broken" class="align-middle fs-18"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Booking Workflow</h4></div>
            <div class="card-body">
                <div class="alert {{ $remainingContractDays > 0 ? 'alert-success' : 'alert-warning' }}">
                    <strong>{{ $contractDays }} of 90 contract days used.</strong><br>
                    @if($remainingContractDays > 0)
                        {{ $remainingContractDays }} days remain before renewal is required. Maximum checkout: {{ $contractLimitDate?->format('d M Y') }}.
                    @else
                        The 90-day limit is reached. Use Renew Contract; a new DTCM fee is required.
                    @endif
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
                    <div class="col-md-6"><label class="form-label">Amount (AED)</label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required></div>
                    <div class="col-md-6"><label class="form-label">Payment Method</label><select name="payment_method" class="form-select" required><option>Bank Transfer</option><option>Cash</option><option>Card</option><option>Cheque</option><option>Online Payment</option></select></div>
                    <div class="col-md-6"><label class="form-label">Deposit To Account</label><select name="bank_account_id" class="form-select"><option value="">Not selected</option>@foreach($bankAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Of this payment: security deposit (AED)</label><input name="deposit_amount" type="number" class="form-control" min="0" step="0.01" value="0"><small class="text-muted">Included in the payment, not an additional charge. Allocates this portion to the deposit wallet.</small></div>
                    <div class="col-md-6"><label class="form-label">Reference</label><input name="reference" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Upload Receipt</label><input type="file" name="receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
                    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Payment</button></div>
        </form>
    </div></div>
</div>
@endforeach

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
@endsection
