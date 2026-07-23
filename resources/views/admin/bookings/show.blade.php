@extends('layouts.app')

@section('content')
<div class="row">
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
                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#extendBookingModal" title="Extend Booking" aria-label="Extend Booking">
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
                    <form action="{{ route('admin.booking.check-out', $booking->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-outline-dark w-100" {{ $booking->checked_out_at ? 'disabled' : '' }}>
                            <iconify-icon icon="solar:clipboard-remove-broken" class="align-middle fs-18"></iconify-icon>
                            Complete Check Out & Create Tasks
                        </button>
                    </form>
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

        <div class="card">
            <div class="card-header"><h4 class="card-title mb-0">Payment Proof</h4></div>
            <div class="card-body">
                @if($booking->payment_proof)
                    <a href="{{ asset($booking->payment_proof) }}" target="_blank" class="btn btn-sm btn-success mb-3">View Attached Receipt</a>
                @endif
                <form action="{{ route('admin.booking.payment-proof', $booking->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label class="form-label" for="payment_proof">Attach receipt with invoice</label>
                    <input type="file" class="form-control mb-3" id="payment_proof" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png" required>
                    @error('payment_proof')<span class="text-danger">{{ $message }}</span>@enderror
                    <button class="btn btn-dark w-100">Mark Invoice Paid</button>
                </form>
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
                    <div class="mb-3">
                        <label for="extend_check_out" class="form-label">New Check Out Date</label>
                        <input type="date" id="extend_check_out" name="check_out" class="form-control" value="{{ old('check_out', $booking->check_out?->copy()->addDay()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="extend_check_out_time" class="form-label">Check Out Time</label>
                        <input type="time" id="extend_check_out_time" name="check_out_time" class="form-control" value="{{ old('check_out_time', $booking->check_out_time ? \Carbon\Carbon::parse($booking->check_out_time)->format('H:i') : '11:00') }}">
                    </div>
                    <div class="mb-3">
                        <label for="extension_rent_amount" class="form-label">Additional Rent Amount</label>
                        <input type="number" step="0.01" min="0" id="extension_rent_amount" name="extension_rent_amount" class="form-control" value="{{ old('extension_rent_amount', 0) }}">
                    </div>
                    <div class="mb-0">
                        <label for="extension_notes" class="form-label">Notes</label>
                        <textarea id="extension_notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Extension</button>
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
                            <input type="date" id="renew_check_out" name="check_out" class="form-control" value="{{ old('check_out', $booking->check_out?->copy()->addMonth()->format('Y-m-d')) }}" required>
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
                        </div>
                        <div class="col-lg-12">
                            <label for="renew_notes" class="form-label">Notes</label>
                            <textarea id="renew_notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Renewal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
