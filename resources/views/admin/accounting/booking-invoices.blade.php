@extends('layouts.app')

@section('content')
@include('admin.accounting.partials.module-nav')

<div class="card">
    <div class="card-header"><h4 class="card-title mb-0">Booking, Renewal & Extension Invoices</h4></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light-subtle"><tr><th>Invoice</th><th>Type</th><th>Booking</th><th>Guest</th><th>Unit</th><th>Period</th><th>Total</th><th>Status</th><th>PDF</th></tr></thead>
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
                    <td><span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : 'bg-warning' }}">{{ ucfirst($invoice->status) }}</span></td>
                    <td><a class="btn btn-sm btn-soft-primary" href="{{ route('admin.accounting.booking-invoices.pdf', $invoice->id) }}"><i class="ri-file-pdf-2-line"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No booking invoices generated yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $invoices->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
