@php
    $allInvoicesPaid = $booking->invoices->isNotEmpty() && $booking->invoices->every(fn ($invoice) => $invoice->status === 'paid' && $invoice->balance_due <= 0);
@endphp
<div class="table-responsive">
    <table class="table align-middle mb-2">
        <thead><tr><th>Invoice / Period</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($booking->invoices as $invoice)
            <tr>
                <td><strong>{{ $invoice->invoice_number }}</strong><small class="d-block text-muted">{{ $invoice->period_from?->format('d M Y') }} – {{ $invoice->period_to?->format('d M Y') }}</small></td>
                <td class="text-end">AED {{ number_format((float) $invoice->total_amount, 2) }}</td>
                <td class="text-end text-success">AED {{ number_format($invoice->paid_amount, 2) }}</td>
                <td class="text-end {{ $invoice->balance_due > 0 ? 'text-danger' : 'text-success' }}">AED {{ number_format($invoice->balance_due, 2) }}</td>
                <td><span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : ($invoice->status === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($invoice->status) }}</span></td>
            </tr>
            <tr><td colspan="5">
                <div class="d-flex gap-2 flex-wrap mb-2">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('guest.booking.invoice-document', [$booking->booking_reference, $invoice]) }}">Invoice PDF</a>
                    @if($invoice->payments->isNotEmpty())<a class="btn btn-sm btn-outline-secondary" href="{{ route('guest.booking.invoice-receipt', [$booking->booking_reference, $invoice]) }}">Payment receipt</a>@endif
                    @if($invoice->status === 'paid' && $invoice->balance_due <= 0)
                        <a class="btn btn-sm btn-outline-success" href="{{ route('guest.booking.invoice-confirmation', [$booking->booking_reference, $invoice]) }}">Period confirmation</a>
                    @else
                        <span class="small text-muted align-self-center">Confirmation available after full payment.</span>
                    @endif
                </div>
                @if($invoice->payments->isNotEmpty())
                    <div class="small"><strong>Payments received</strong>
                    @foreach($invoice->payments->sortBy('payment_date') as $payment)
                        <div class="d-flex justify-content-between gap-2 border-top py-1"><span>{{ $payment->payment_date?->format('d M Y') }} · {{ $payment->payment_method }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</span><strong>AED {{ number_format((float) $payment->amount, 2) }}</strong></div>
                    @endforeach
                    </div>
                @else
                    <small class="text-muted">No payment received yet.</small>
                @endif
            </td></tr>
        @empty
            <tr><td colspan="5" class="text-muted">No itemised invoices are available.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if($allInvoicesPaid)
    <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-success flex-fill" href="{{ route('guest.booking.confirmation', $booking->booking_reference) }}">Booking confirmation</a><a class="btn btn-success flex-fill" href="{{ route('guest.booking.complete-pack', $booking->booking_reference) }}">Complete booking pack</a></div>
@elseif($booking->invoices->isNotEmpty())
    <p class="small text-warning mb-0">Full booking confirmation is locked until all invoice balances are paid.</p>
@endif
