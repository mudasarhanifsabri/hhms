@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">Booking, Renewal & Extension Invoices</h4></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Invoice</th><th>Type</th><th>Booking</th><th>Guest</th><th>Unit</th><th>Period</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->type_label }}</td>
                    <td>{{ $invoice->booking?->booking_reference }}</td>
                    <td>{{ $invoice->booking?->guest_name }}</td>
                    <td>{{ $invoice->booking?->property?->name ?? '-' }}</td>
                    <td>{{ $invoice->period_from?->format('d M Y') }} - {{ $invoice->period_to?->format('d M Y') }}</td>
                    <td>AED {{ number_format((float) $invoice->total_amount, 2) }}</td>
                    <td class="text-success">AED {{ number_format($invoice->paid_amount, 2) }}</td>
                    <td class="{{ $invoice->balance_due > 0 ? 'text-danger' : 'text-success' }}">AED {{ number_format($invoice->balance_due, 2) }}</td>
                    <td><span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : ($invoice->status === 'partial' ? 'bg-warning' : 'bg-danger') }}">{{ ucfirst($invoice->status) }}</span></td>
                    <td><div class="d-flex gap-1">
                        <a class="btn btn-sm btn-soft-primary" href="{{ route('admin.accounting.booking-invoices.pdf', $invoice->id) }}" title="Download PDF"><i class="ri-file-pdf-2-line"></i></a>
                        @can('accounting.manage')<button class="btn btn-sm btn-soft-warning" type="button" data-bs-toggle="modal" data-bs-target="#editInvoice{{ $invoice->id }}" title="Edit invoice"><i class="ri-edit-line"></i></button>@endcan
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted py-4">No booking invoices generated yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $invoices->links('pagination::bootstrap-5') }}</div>
</div>

@can('accounting.manage')
@foreach($invoices as $invoice)
<div class="modal fade" id="editInvoice{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('admin.booking-invoice.correct', $invoice) }}">
            @csrf @method('PUT')
            <div class="modal-header"><div><h5 class="modal-title">Edit Booking Invoice</h5><small class="text-muted">{{ $invoice->invoice_number }} · {{ $invoice->booking?->guest_name }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="alert alert-info">For financial safety, the invoice remains unchanged until the correction is approved. Payments already received cannot exceed the corrected total.</div><div class="row g-3">
                <div class="col-md-4"><label class="form-label">Rent Amount</label><input class="form-control" type="number" step="0.01" min="0" name="rent_amount" value="{{ $invoice->rent_amount }}" required></div>
                <div class="col-md-4"><label class="form-label">VAT Rate %</label><input class="form-control" type="number" step="0.01" min="0" max="100" name="vat_rate" value="{{ $invoice->vat_rate }}" required></div>
                <div class="col-md-4 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="vat_included" value="1" id="vatIncluded{{ $invoice->id }}" @checked($invoice->vat_included)><label class="form-check-label" for="vatIncluded{{ $invoice->id }}">Rent includes VAT</label></div></div>
                @foreach(['DTCM Fee','Cleaning Fee','Agency Fee','Other Fees','Security Deposit'] as $feeLabel)
                    <div class="col-md-4"><label class="form-label">{{ $feeLabel }}</label><input class="form-control" type="number" step="0.01" min="0" name="fees[{{ $feeLabel }}]" value="{{ number_format((float)($invoice->fees[$feeLabel] ?? 0), 2, '.', '') }}"></div>
                @endforeach
                <div class="col-12"><label class="form-label">Reason for Editing</label><textarea class="form-control" name="reason" rows="3" minlength="5" maxlength="1000" required placeholder="Explain why this invoice needs correction"></textarea></div>
                <div class="col-12"><label class="form-label">Your Password</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required><div class="form-text">Required to submit this financial correction for approval.</div></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-warning"><i class="ri-send-plane-line me-1"></i>Submit Edit for Approval</button></div>
        </form>
    </div>
</div>
@endforeach
@endcan
@endsection
