<div class="card">
    <div class="card-header"><h4 class="mb-0">Invoices & Payment Corrections</h4></div>
    <div class="card-body"><p class="text-muted mb-0">History is preserved. Edit an unpaid invoice, correct a payment reference/notes, or reverse an eligible incorrect payment and re-enter it. Reversal is a bookkeeping correction, not a bank refund. Deposit-linked amounts are locked.</p>
        @if($booking->owner_posting_basis !== 'receipts')<div class="alert alert-warning mt-3 mb-0">Legacy booking: owner postings require reconciliation before payment amounts can be reversed. Reference and notes remain editable.</div>@endif
    </div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Invoice</th><th>Total</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    @foreach($booking->invoices as $invoice)
        <tr><td>{{ $invoice->invoice_number }}<small class="d-block">{{ $invoice->type_label }}</small></td><td>AED {{ number_format((float)$invoice->total_amount,2) }}</td><td>AED {{ number_format($invoice->paid_amount,2) }}</td><td>{{ ucfirst($invoice->status) }}</td><td>
            @if($invoice->status==='unpaid' && $invoice->allPayments->whereNull('reversed_at')->isEmpty())<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#correctInvoice{{ $invoice->id }}">Edit Invoice</button>@else<span class="small text-muted">Amounts locked while payments are active</span>@endif
            <a class="btn btn-sm btn-light" href="{{ route('admin.booking.show',$booking) }}#bookingInvoices">Record Payment / Documents</a>
        </td></tr>
        @foreach($invoice->allPayments as $payment)
        <tr><td colspan="2"><span class="badge {{ $payment->reversed_at?'bg-danger':'bg-success' }}">{{ $payment->reversed_at?'Reversed':'Payment' }}</span> {{ $payment->payment_date?->format('d M Y') }} · AED {{ number_format((float)$payment->amount,2) }}<small class="d-block text-muted">{{ $payment->bankAccount?->name ?? 'No account' }} · {{ $payment->reference }}</small></td><td colspan="2">{{ $payment->payment_method }}<small class="d-block">Rent allocation: {{ $payment->rent_amount === null ? 'Legacy / unclassified' : 'AED '.number_format((float)$payment->rent_amount,2) }}</small></td><td>
            @unless($payment->reversed_at)<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#correctPayment{{ $payment->id }}">Edit Payment Details</button>
            @if($booking->owner_posting_basis==='receipts')<button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reversePayment{{ $payment->id }}">Reverse & Re-enter</button>@endif
            @endunless
        </td></tr>
        @endforeach
    @endforeach
    </tbody></table></div>
</div>
@foreach($booking->invoices as $invoice)
<div class="modal fade" id="correctInvoice{{ $invoice->id }}" tabindex="-1" aria-label="Edit invoice" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="{{ route('admin.booking-invoice.correct',$invoice) }}">@csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit {{ $invoice->invoice_number }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-6"><label class="form-label">Rent excluding VAT</label><input name="rent_amount" type="number" min="0" step="0.01" value="{{ $invoice->rent_amount }}" class="form-control" required></div>
            <div class="col-6"><label class="form-label">VAT rate (%) on rent</label><input name="vat_rate" type="number" min="0" max="100" step="0.01" value="{{ $invoice->vat_rate }}" class="form-control" required></div>
            @foreach($invoice->fees ?? [] as $label=>$amount)<div class="col-6"><label class="form-label">{{ $label }}</label><input name="fees[{{ $label }}]" type="number" min="0" step="0.01" value="{{ $amount }}" class="form-control" required></div>@endforeach
            <div class="col-12"><label class="form-label">Reason for correction</label><textarea name="reason" class="form-control" minlength="5" required></textarea></div>
        </div><p class="small text-muted mt-3">VAT and total are recalculated. Invoice number and original history remain unchanged.</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Invoice Correction</button></div>
    </form>
</div></div></div>
@foreach($invoice->allPayments as $payment)
<div class="modal fade" id="correctPayment{{ $payment->id }}" tabindex="-1" aria-label="Edit payment details" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="{{ route('admin.booking-payment.details',$payment) }}">@csrf @method('PUT')
        <div class="modal-header"><h5>Edit Payment Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><p>{{ $invoice->invoice_number }} · AED {{ number_format((float)$payment->amount,2) }}</p><label class="form-label">Reference</label><input name="reference" value="{{ $payment->reference }}" class="form-control mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control mb-3">{{ $payment->notes }}</textarea><label class="form-label">Reason for correction</label><textarea name="reason" minlength="5" class="form-control" required></textarea><p class="small text-muted mt-3">Amount, date, bank account and method are protected. To correct them, use Reverse & Re-enter where available.</p></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save Details</button></div>
    </form>
</div></div></div>
<div class="modal fade" id="reversePayment{{ $payment->id }}" tabindex="-1" aria-label="Reverse incorrect payment" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="{{ route('admin.booking-payment.reverse',$payment) }}">@csrf
        <div class="modal-header"><h5>Reverse Incorrect Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div class="alert alert-warning">Reverse AED {{ number_format((float)$payment->amount,2) }} on {{ $invoice->invoice_number }}? This reverses the recorded account and owner postings, reopens the invoice balance and preserves the original receipt. It does not send money.</div><label class="form-label">Reason</label><textarea name="reason" minlength="5" class="form-control mb-3" required></textarea><label><input type="checkbox" name="confirm" value="1" required> This record is incorrect, not an actual guest refund.</label></div>
        <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Confirm Reversal</button></div>
    </form>
</div></div></div>
@endforeach
@endforeach
